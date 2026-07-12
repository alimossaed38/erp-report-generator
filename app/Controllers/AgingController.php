<?php

final class AgingController extends Controller
{
    private const BUCKET_LABELS = [
        'current' => 'جاري',
        'd1_30' => '1-30 يوم',
        'd31_60' => '31-60 يوم',
        'd61_90' => '61-90 يوم',
        'd90_plus' => 'أكثر من 90 يوم',
    ];

    public function index(): void
    {
        $repo = new SalesRepository();
        $invoices = $repo->agingInvoices();
        $asOf = $repo->dateBounds()['max'];
        $buckets = $asOf !== null ? Analytics::agingBuckets($invoices, $asOf) : [
            'current' => 0.0, 'd1_30' => 0.0, 'd31_60' => 0.0, 'd61_90' => 0.0, 'd90_plus' => 0.0,
            'total' => 0.0, 'counts' => ['current' => 0, 'd1_30' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd90_plus' => 0],
        ];

        $enriched = $invoices;
        if ($asOf !== null) {
            $asOfDate = new DateTimeImmutable($asOf);
            $enriched = array_map(function (array $row) use ($asOfDate): array {
                $dueDate = new DateTimeImmutable($row['due_date']);
                $daysLate = (int) round(($asOfDate->getTimestamp() - $dueDate->getTimestamp()) / 86400);
                $bucketKey = match (true) {
                    $daysLate <= 0 => 'current',
                    $daysLate <= 30 => 'd1_30',
                    $daysLate <= 60 => 'd31_60',
                    $daysLate <= 90 => 'd61_90',
                    default => 'd90_plus',
                };

                $row['days_late'] = $daysLate;
                $row['bucket'] = $bucketKey;
                $row['bucket_label'] = self::BUCKET_LABELS[$bucketKey];

                return $row;
            }, $invoices);

            usort($enriched, static fn(array $a, array $b): int => $b['days_late'] <=> $a['days_late']);
        }

        $this->render('aging', [
            'title' => 'أعمار الذمم',
            'subtitle' => 'راقب المستحقات المتأخرة من العملاء موزعة حسب فترات التأخير.',
            'active' => 'aging',
            'buckets' => $buckets,
            'bucketLabels' => self::BUCKET_LABELS,
            'invoices' => $enriched,
            'asOf' => $asOf,
        ]);
    }
}
