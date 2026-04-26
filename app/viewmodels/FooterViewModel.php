<?php
// Arxeio: app\viewmodels\FooterViewModel.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.

require_once __DIR__ . '/../includes/site_context.php';

class FooterViewModel
{
// Ftiaxnei lista links tou footer me context-aware entries kai prosthetei parent-only links otan xreiazetai.
    public function buildLinks(): array
    {
        $links = [
            ['label' => 'Î‘ÏÏ‡Î¹ÎºÎ®', 'href' => site_section_url('home.php')],
            ['label' => 'Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½', 'href' => site_section_url('parents.php')],
            ['label' => 'Î‘Î½Î±ÎºÎ¿Î¹Î½ÏŽÏƒÎµÎ¹Ï‚', 'href' => site_section_url('announcements.php')],
            ['label' => 'Î•ÎºÎ´Î·Î»ÏŽÏƒÎµÎ¹Ï‚', 'href' => site_section_url('events.php')],
            ['label' => 'Î§ÏÎ®ÏƒÎ¹Î¼ÎµÏ‚ Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚', 'href' => site_section_url('useful-information.php')],
            ['label' => 'Î‘Î¹Ï„Î®ÏƒÎµÎ¹Ï‚', 'href' => site_section_url('applications.php')],
        ];

        if (site_is_parent()) {
            $links[] = ['label' => 'ÎšÎ±Ï„Î¬ÏƒÏ„Î·Î¼Î±', 'href' => site_section_url('eshop.php')];
            $links[] = ['label' => 'Î¦Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¯ÎµÏ‚', 'href' => site_section_url('photos.php')];
        }

        $links[] = ['label' => 'Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±', 'href' => site_section_url('epikoinonia.php')];

        return $links;
    }
}
