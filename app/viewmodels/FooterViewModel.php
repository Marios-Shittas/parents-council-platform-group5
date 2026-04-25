<?php

require_once __DIR__ . '/../includes/site_context.php';

class FooterViewModel
{
    // Ftiaxnei ta links tou footer analoga me to public/parent context.
    public function buildLinks(): array
    {
        $links = [
            ['label' => 'Αρχική', 'href' => site_section_url('home.php')],
            ['label' => 'Σύνδεσμος Γονέων', 'href' => site_section_url('parents.php')],
            ['label' => 'Ανακοινώσεις', 'href' => site_section_url('announcements.php')],
            ['label' => 'Εκδηλώσεις', 'href' => site_section_url('events.php')],
            ['label' => 'Χρήσιμες Πληροφορίες', 'href' => site_section_url('useful-information.php')],
            ['label' => 'Αιτήσεις', 'href' => site_section_url('applications.php')],
        ];

        if (site_is_parent()) {
            $links[] = ['label' => 'Κατάστημα', 'href' => site_section_url('eshop.php')];
            $links[] = ['label' => 'Φωτογραφίες', 'href' => site_section_url('photos.php')];
        }

        $links[] = ['label' => 'Επικοινωνία', 'href' => site_section_url('epikoinonia.php')];

        return $links;
    }
}
