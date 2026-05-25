document.addEventListener('DOMContentLoaded', function () {
    const dropdowns = Array.from(document.querySelectorAll('.profile-dropdown'));

    if (dropdowns.length === 0) {
        return;
    }

    function closeDropdown(dropdown) {
        const toggle = dropdown.querySelector('[data-dropdown-toggle="true"]');
        dropdown.classList.remove('is-open');

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    function openDropdown(dropdown) {
        const toggle = dropdown.querySelector('[data-dropdown-toggle="true"]');
        dropdown.classList.add('is-open');

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
    }

    function closeAllExcept(activeDropdown) {
        dropdowns.forEach((dropdown) => {
            if (dropdown !== activeDropdown) {
                closeDropdown(dropdown);
            }
        });
    }

    dropdowns.forEach((dropdown) => {
        const toggle = dropdown.querySelector('[data-dropdown-toggle="true"]');

        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const willOpen = !dropdown.classList.contains('is-open');
            closeAllExcept(dropdown);

            if (willOpen) {
                openDropdown(dropdown);
                return;
            }

            closeDropdown(dropdown);
        });

        const menu = dropdown.querySelector('.profile-dropdown-menu');
        if (menu) {
            menu.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        }
    });

    document.addEventListener('click', function (event) {
        dropdowns.forEach((dropdown) => {
            if (!dropdown.contains(event.target)) {
                closeDropdown(dropdown);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        dropdowns.forEach((dropdown) => {
            closeDropdown(dropdown);
        });
    });
});
