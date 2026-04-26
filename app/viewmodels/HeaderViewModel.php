<?php
// Arxeio: app\viewmodels\HeaderViewModel.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.

require_once __DIR__ . '/../includes/site_context.php';

class HeaderViewModel
{
// Synthetei ola ta dedomena pou xreiazetai to header provoli: current route marker,
// portal label, metadata tou profile link kai teliki lista navigation entries.
    public function build(): array
    {
        $currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
        $navItems = $this->buildNavItems();

        return [
            'site_title' => 'Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½ & ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Ï‰Î½',
            'current_page' => $currentPage,
            'portal_label' => site_is_parent() ? 'Î§ÏŽÏÎ¿Ï‚ Î“Î¿Î½Î­Î±' : 'Î”Î·Î¼ÏŒÏƒÎ¹Î± Î ÏÎ»Î·',
            'profile_item' => [
                'label' => 'Î¤Î¿ Î ÏÎ¿Ï†Î¯Î» ÎœÎ¿Ï…',
                'href' => site_section_url('profile.php'),
                'icon' => 'fas fa-user-circle',
                'match' => ['profile.php'],
            ],
            'nav_items' => $navItems,
        ];
    }
// Dimiourgei ti domi tou navigation menu kai prosthetei conditionally parent-only items
// (shop/photos) analoga me to trexon site context.
    private function buildNavItems(): array
    {
        $navItems = [
            [
                'label' => 'Î‘ÏÏ‡Î¹ÎºÎ®',
                'href' => site_section_url('home.php'),
                'icon' => 'fas fa-home',
                'match' => ['home.php', 'index.php', ''],
            ],
            [
                'label' => 'Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½',
                'href' => site_section_url('parents.php'),
                'icon' => 'fas fa-users',
                'match' => ['parents.php'],
            ],
            [
                'label' => 'Î‘Î½Î±ÎºÎ¿Î¹Î½ÏŽÏƒÎµÎ¹Ï‚',
                'href' => site_section_url('announcements.php'),
                'icon' => 'fas fa-bullhorn',
                'match' => ['announcements.php'],
            ],
            [
                'label' => 'Î•ÎºÎ´Î·Î»ÏŽÏƒÎµÎ¹Ï‚',
                'href' => site_section_url('events.php'),
                'icon' => 'fas fa-calendar-alt',
                'match' => ['events.php', 'event.php'],
            ],
            [
                'label' => 'Î§ÏÎ®ÏƒÎ¹Î¼ÎµÏ‚ Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚',
                'href' => site_section_url('useful-information.php'),
                'icon' => 'fas fa-info-circle',
                'match' => ['useful-information.php'],
            ],
            [
                'label' => 'Î‘Î¹Ï„Î®ÏƒÎµÎ¹Ï‚',
                'href' => site_section_url('applications.php'),
                'icon' => 'fas fa-file-alt',
                'match' => ['applications.php'],
            ],
        ];

        if (site_is_parent()) {
            $navItems[] = [
                'label' => 'ÎšÎ±Ï„Î¬ÏƒÏ„Î·Î¼Î±',
                'href' => site_section_url('eshop.php'),
                'icon' => 'fas fa-store',
                'match' => ['eshop.php'],
            ];
        }

        $navItems[] = [
            'label' => 'Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±',
            'href' => site_section_url('epikoinonia.php'),
            'icon' => 'fas fa-envelope',
            'match' => ['epikoinonia.php'],
        ];

        if (site_is_parent()) {
            $navItems[] = [
                'label' => 'Î¦Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¯ÎµÏ‚',
                'href' => site_section_url('photos.php'),
                'icon' => 'fas fa-camera',
                'match' => ['photos.php'],
            ];
        }

        return $navItems;
    }
}
