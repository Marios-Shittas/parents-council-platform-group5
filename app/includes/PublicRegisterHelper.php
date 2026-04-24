<?php

final class PublicRegisterHelper
{
    /**
     * Returns whether registration is currently open and all active registration periods.
     */
    public static function registrationWindowState(mysqli $conn): array
    {
        $stmt = $conn->prepare(
            "SELECT start_date, end_date, ss_status
             FROM SystemSchedule
             WHERE feature = 'registration'
             ORDER BY start_date ASC, ss_id ASC"
        );

        if (!$stmt) {
            return [
                'is_open' => false,
                'message' => 'Η περίοδος εγγραφών είναι κλειστή.',
                'periods' => [],
            ];
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $schedules = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        if (empty($schedules)) {
            return [
                'is_open' => false,
                'message' => 'Η περίοδος εγγραφών είναι κλειστή.',
                'periods' => [],
            ];
        }

        $now = time();
        $formattedPeriods = [];

        foreach ($schedules as $schedule) {
            $status = (string)($schedule['ss_status'] ?? 'inactive');
            if ($status !== 'active') {
                continue;
            }

            $start = !empty($schedule['start_date']) ? strtotime((string)$schedule['start_date']) : false;
            $end = !empty($schedule['end_date']) ? strtotime((string)$schedule['end_date']) : false;
            if ($start === false || $end === false) {
                continue;
            }

            $formattedPeriods[] = date('d/m/Y H:i', $start) . ' - ' . date('d/m/Y H:i', $end);

            if ($now >= $start && $now <= $end) {
                return [
                    'is_open' => true,
                    'message' => '',
                    'periods' => $formattedPeriods,
                ];
            }
        }

        if (empty($formattedPeriods)) {
            return [
                'is_open' => false,
                'message' => 'Η περίοδος εγγραφών είναι κλειστή.',
                'periods' => [],
            ];
        }

        return [
            'is_open' => false,
            'message' => 'Η περίοδος εγγραφών είναι κλειστή.',
            'periods' => $formattedPeriods,
        ];
    }
}
