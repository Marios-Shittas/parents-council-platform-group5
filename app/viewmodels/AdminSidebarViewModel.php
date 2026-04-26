<?php

class AdminSidebarViewModel
{
    private ?mysqli $conn;
// Dexetai optional DB connection pou xrisimopoieitai apo services me explicit mysqli injection.
    public function __construct(?mysqli $conn = null)
    {
        $this->conn = $conn;
    }
// Synkentronei olous tous counters tou admin sidebar (messages/xristes/applications/orders)
// kai epistrefei current page marker mazi me preformatted badge texts.
    public function build(): array
    {
        $unreadContactMessages = $this->safeCount(function (): int {
            require_once __DIR__ . '/../services/EpikoinoniaService.php';
            $service = new EpikoinoniaService();

            return (int) $service->getUnreadMessageCount();
        });

        $pendingUserRegistrations = $this->safeCount(function (): int {
            require_once __DIR__ . '/../services/UsersService.php';
            $service = new UsersService();
            $service->runScheduledMaintenance();

            return (int) $service->getPendingRegistrationCount();
        });

        $pendingApplicationSubmissions = $this->safeCount(function (): int {
            require_once __DIR__ . '/../services/ApplicationsService.php';
            $service = new ApplicationsService();

            return (int) $service->getWaitingSubmissionCount();
        });

        $pendingPaidOrders = $this->safeCount(function (): int {
            require_once __DIR__ . '/../services/OrdersService.php';
            $service = new OrdersService($this->conn);

            return (int) $service->getPendingPaidOrdersCount();
        });

        return [
            'current_page' => basename($_SERVER['PHP_SELF']),
            'epikoinonia_badge_text' => $this->formatBadge($unreadContactMessages),
            'users_badge_text' => $this->formatBadge($pendingUserRegistrations),
            'applications_badge_text' => $this->formatBadge($pendingApplicationSubmissions),
            'orders_badge_text' => $this->formatBadge($pendingPaidOrders),
        ];
    }
// Trexei ton resolver asfales; an ypiresia rixei exception, epistrefei 0 gia na meinei stathero to sidebar UI.
    private function safeCount(callable $resolver): int
    {
        try {
            return max(0, (int) $resolver());
        } catch (Throwable $exception) {
            return 0;
        }
    }
// Metatrepei to raw arithmitiko count se compact badge text (keno sto 0, cap sto 10+).
    private function formatBadge(int $count): string
    {
        if ($count <= 0) {
            return '';
        }

        return $count > 10 ? '10+' : (string) $count;
    }
}
