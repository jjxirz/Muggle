<?php

class OpenLibraryService
{
    public function getBookByIdentifier(string $identifier, string $mode = 'isbn'): ?array
    {
        $mode = strtolower(trim($mode));
        if ($mode === 'doi') {
            return $this->getBookByDoi($identifier);
        }

        return $this->getBookByIsbn($identifier);
    }

    public function searchTitles(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $apiUrl = 'https://openlibrary.org/search.json?title=' . urlencode($query) . '&limit=8';
        $payload = $this->requestJson($apiUrl);
        if ($payload === null || !isset($payload['docs']) || !is_array($payload['docs'])) {
            return [];
        }

        $results = [];
        foreach ($payload['docs'] as $doc) {
            if (!is_array($doc) || empty($doc['title'])) {
                continue;
            }

            $author = '';
            if (isset($doc['author_name']) && is_array($doc['author_name']) && count($doc['author_name']) > 0) {
                $author = (string) $doc['author_name'][0];
            }

            $isbn = '';
            if (isset($doc['isbn']) && is_array($doc['isbn']) && count($doc['isbn']) > 0) {
                $isbn = (string) $doc['isbn'][0];
            }

            $results[] = [
                'titulo' => trim((string) $doc['title']),
                'autor' => trim($author),
                'isbn' => trim($isbn),
            ];
        }

        return $results;
    }

    public function getBookByIsbn(string $isbn): ?array
    {
        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        if ($cleanIsbn === null || $cleanIsbn === '') {
            return null;
        }

        $apiUrl = 'https://openlibrary.org/api/books?bibkeys=ISBN:' . urlencode($cleanIsbn) . '&format=json&jscmd=details';
        $payload = $this->requestJson($apiUrl);
        if ($payload === null) {
            return null;
        }

        $key = 'ISBN:' . $cleanIsbn;
        if (!isset($payload[$key]['details']) || !is_array($payload[$key]['details'])) {
            return null;
        }

        $details = $payload[$key]['details'];
        $title = isset($details['title']) ? trim((string) $details['title']) : '';
        if ($title === '') {
            return null;
        }

        $author = '';
        if (isset($details['authors']) && is_array($details['authors']) && count($details['authors']) > 0) {
            $authorNames = [];
            foreach ($details['authors'] as $authorItem) {
                if (is_array($authorItem) && isset($authorItem['name'])) {
                    $authorNames[] = trim((string) $authorItem['name']);
                }
            }
            $author = implode(', ', array_filter($authorNames));
        }

        $description = '';
        if (isset($details['description'])) {
            if (is_array($details['description']) && isset($details['description']['value'])) {
                $description = trim((string) $details['description']['value']);
            } elseif (is_string($details['description'])) {
                $description = trim($details['description']);
            }
        }

        $publishDate = null;
        if (isset($details['publish_date'])) {
            $publishDate = $this->normalizeDate((string) $details['publish_date']);
        }

        $cover = 'https://covers.openlibrary.org/b/isbn/' . urlencode($cleanIsbn) . '-L.jpg';

        return [
            'isbn' => $cleanIsbn,
            'doi' => null,
            'titulo' => $title,
            'autor' => $author,
            'descripcion' => $description,
            'portada' => $cover,
            'fecha_publicado' => $publishDate,
        ];
    }

    public function getBookByDoi(string $doi): ?array
    {
        $doi = trim($doi);
        if ($doi === '') {
            return null;
        }

        $apiUrl = 'https://api.crossref.org/works/' . rawurlencode($doi);
        $payload = $this->requestJson($apiUrl);
        if ($payload === null || !isset($payload['message']) || !is_array($payload['message'])) {
            return null;
        }

        $message = $payload['message'];
        $title = '';
        if (isset($message['title']) && is_array($message['title']) && count($message['title']) > 0) {
            $title = trim((string) $message['title'][0]);
        }

        if ($title === '') {
            return null;
        }

        $authors = [];
        if (isset($message['author']) && is_array($message['author'])) {
            foreach ($message['author'] as $author) {
                if (!is_array($author)) {
                    continue;
                }

                $given = trim((string) ($author['given'] ?? ''));
                $family = trim((string) ($author['family'] ?? ''));
                $name = trim($given . ' ' . $family);
                if ($name !== '') {
                    $authors[] = $name;
                }
            }
        }

        $isbn = null;
        if (isset($message['ISBN']) && is_array($message['ISBN']) && count($message['ISBN']) > 0) {
            $isbn = trim((string) $message['ISBN'][0]);
        }

        $description = '';
        if (isset($message['abstract']) && is_string($message['abstract'])) {
            $description = trim(strip_tags($message['abstract']));
        }

        $publishDate = null;
        if (isset($message['issued']['date-parts'][0]) && is_array($message['issued']['date-parts'][0])) {
            $parts = $message['issued']['date-parts'][0];
            $year = isset($parts[0]) ? (int) $parts[0] : 0;
            $month = isset($parts[1]) ? (int) $parts[1] : 1;
            $day = isset($parts[2]) ? (int) $parts[2] : 1;
            if ($year > 0) {
                $publishDate = sprintf('%04d-%02d-%02d', $year, max(1, $month), max(1, $day));
            }
        }

        $cover = null;
        if ($isbn !== null && $isbn !== '') {
            $cover = 'https://covers.openlibrary.org/b/isbn/' . urlencode($isbn) . '-L.jpg';
        }

        return [
            'isbn' => $isbn,
            'doi' => $doi,
            'titulo' => $title,
            'autor' => implode(', ', $authors),
            'descripcion' => $description,
            'portada' => $cover,
            'fecha_publicado' => $publishDate,
        ];
    }

    private function requestJson(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => "User-Agent: MuggleLibrary/1.0\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $payload = json_decode($response, true);
        if (!is_array($payload)) {
            return null;
        }

        return $payload;
    }

    private function normalizeDate(string $rawDate): ?string
    {
        $rawDate = trim($rawDate);
        if ($rawDate === '') {
            return null;
        }

        $formats = ['Y-m-d', 'Y/m/d', 'Y-m', 'Y'];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $rawDate);
            if ($date instanceof DateTime) {
                if ($format === 'Y') {
                    return $date->format('Y-01-01');
                }
                if ($format === 'Y-m') {
                    return $date->format('Y-m-01');
                }
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($rawDate);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }
}
