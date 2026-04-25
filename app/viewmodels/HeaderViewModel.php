<?php

require_once __DIR__ . '/../includes/site_context.php';

class HeaderViewModel
{
    // Ftiaxnei ola ta dedomena pou xreiazetai to public header.
    public function build(): array
    {
        $currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
        $navItems = $this->buildNavItems();

        return [
            'site_title' => 'Σύνδεσμος Γονέων & Κηδεμόνων',
            'current_page' => $currentPage,
            'portal_label' => site_is_parent() ? 'Χώρος Γονέα' : 'Δημόσια Πύλη',
            'profile_item' => [
                'label' => 'Το Προφίλ Μου',
                'href' => site_section_url('profile.php'),
                'icon' => 'fas fa-user-circle',
                'match' => ['profile.php'],
            ],
            'nav_items' => $navItems,
        ];
    }

    // Ftiaxnei to menu analoga me to public/parent context.
    private function buildNavItems(): array
    {
        $navItems = [
            [
                'label' => 'Αρχική',
                'href' => site_section_url('home.php'),
                'icon' => 'fas fa-home',
                'match' => ['home.php', 'index.php', ''],
            ],
            [
                'label' => 'Σύνδεσμος Γονέων',
                'href' => site_section_url('parents.php'),
                'icon' => 'fas fa-users',
                'match' => ['parents.php'],
            ],
            [
                'label' => 'Ανακοινώσεις',
                'href' => site_section_url('announcements.php'),
                'icon' => 'fas fa-bullhorn',
                'match' => ['announcements.php'],
            ],
            [
                'label' => 'Εκδηλώσεις',
                'href' => site_section_url('events.php'),
                'icon' => 'fas fa-calendar-alt',
                'match' => ['events.php', 'event.php'],
            ],
            [
                'label' => 'Χρήσιμες Πληροφορίες',
                'href' => site_section_url('useful-information.php'),
                'icon' => 'fas fa-info-circle',
                'match' => ['useful-information.php'],
            ],
            [
                'label' => 'Αιτήσεις',
                'href' => site_section_url('applications.php'),
                'icon' => 'fas fa-file-alt',
                'match' => ['applications.php'],
            ],
        ];

        if (site_is_parent()) {
            $navItems[] = [
                'label' => 'Κατάστημα',
                'href' => site_section_url('eshop.php'),
                'icon' => 'fas fa-store',
                'match' => ['eshop.php'],
            ];
        }

        $navItems[] = [
            'label' => 'Επικοινωνία',
            'href' => site_section_url('epikoinonia.php'),
            'icon' => 'fas fa-envelope',
            'match' => ['epikoinonia.php'],
        ];

        if (site_is_parent()) {
            $navItems[] = [
                'label' => 'Φωτογραφίες',
                'href' => site_section_url('photos.php'),
                'icon' => 'fas fa-camera',
                'match' => ['photos.php'],
            ];
        }

        return $navItems;
    }
}
