<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CustomerTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function title(): string
    {
        return 'Lead Import Template';
    }

    public function headings(): array
    {
        return [
            'Type',
            'Name',
            'Contact Person',
            'Position',
            'Phone',
            'Email',
            'Region',
            'District',
            'Source',
            'Status',
            'Requirements',
            'School Level'
        ];
    }

    public function array(): array
    {
        // Adding a professional sample row
        return [
            [
                'School', 
                'Mlimani Primary School', 
                'Mama Shule', 
                'Headmistress', 
                '0712000000', 
                'info@mlimani.edu', 
                'Arusha', 
                'Arusha City', 
                'Referral', 
                'Potential Customer', 
                'Uniforms, Stationery', 
                'Primary'
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row (1)
        $sheet->getStyle('1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '940000'] // Brand Maroon
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Set height for header
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Style the sample data row (2)
        $sheet->getStyle('2')->getFont()->setItalic(true)->getColor()->setRGB('777777');

        // Add professional borders to a large area
        $sheet->getStyle('A1:L100')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Center align the text for better look
        $sheet->getStyle('A1:L100')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        return [];
    }
}
