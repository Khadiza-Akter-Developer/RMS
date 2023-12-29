<?php
require('../posBackend/fpdf186/fpdf.php');
require_once '../config.php';

function executeQuery($link, $sql) {
    $result = $link->query($sql);
    if ($result === false) {
        echo "Error: " . $link->error;
        return null;
    }
    return $result;
}

function getCategoryRevenue($link, $sql) {
    return executeQuery($link, $sql);
}

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 20);
        $this->Cell(0, 10, "Foodie's Report", 0, 1, 'C');
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function ChapterTitle($title) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, $title, 0, 1, 'L');
        $this->Ln(2);
    }

    function ChapterBody($body) {
        $this->SetFont('Arial', '', 12);
        $this->MultiCell(0, 6, $body);
        $this->Ln(2);
    }

    function CustomTable($header, $data) {
        $w = array(90, 90);

        $this->SetFillColor(200, 200, 200);
        $this->SetFont('Arial', 'B');
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 10, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetFont('Arial', '');
        foreach ($data as $row) {
            for ($i = 0; $i < count($row); $i++) {
                $this->Cell($w[$i], 10, $row[$i], 1);
            }
            $this->Ln();
        }
    }

    function CustomTableThreeColumn($header, $data) {
        $this->SetFont('Arial', 'B', 12);
        foreach ($header as $col) {
            $this->Cell(50, 10, $col, 1);
        }
        $this->Ln();

        $this->SetFont('Arial', '', 12);
        foreach ($data as $row) {
            foreach ($row as $col) {
                $this->Cell(50, 10, $col, 1);
            }
            $this->Ln();
        }
    }

    function CustomTableFourColumn($header, $data) {
        $columnWidths = array(30, 40, 50, 70);

        $this->SetFont('Arial', 'B', 12);
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($columnWidths[$i], 10, $header[$i], 1);
        }
        $this->Ln();

        $this->SetFont('Arial', '', 12);
        foreach ($data as $row) {
            for ($i = 0; $i < count($row); $i++) {
                $this->Cell($columnWidths[$i], 10, $row[$i], 1);
            }
            $this->Ln();
        }
    }
}

$pdf = new PDF();
$pdf->AddPage();

$dailySQL = "SELECT DATE(Bills.bill_time) AS date, DAY(Bills.bill_time) AS day, SUM(Bill_Items.quantity * Menu.item_price) AS daily_category_revenue
             FROM Bills
             JOIN Bill_Items ON Bills.bill_id = Bill_Items.bill_id
             JOIN Menu ON Bill_Items.item_id = Menu.item_id
             GROUP BY DATE(Bills.bill_time), DAY(Bills.bill_time)
             ORDER BY date DESC
             LIMIT 30";
$dailyCategoryRevenue = getCategoryRevenue($link, $dailySQL);

$pdf->ChapterTitle('Daily Revenue Breakdown');
$header = array('Date', 'Day', 'Revenue (BDT)');
$data = array();
while ($row = mysqli_fetch_assoc($dailyCategoryRevenue)) {
    $data[] = array($row['date'], $row['day'], $row['daily_category_revenue']);
}
$pdf->CustomTableThreeColumn($header, $data);

$pdf->Ln();

$weeklySQL = "SELECT CONCAT(YEAR(Bills.bill_time), '-', MONTH(Bills.bill_time)) AS year, WEEK(Bills.bill_time) AS week, SUM(Bill_Items.quantity * Menu.item_price) AS weekly_category_revenue
              FROM Bills
              JOIN Bill_Items ON Bills.bill_id = Bill_Items.bill_id
              JOIN Menu ON Bill_Items.item_id = Menu.item_id
              GROUP BY YEAR(Bills.bill_time), WEEK(Bills.bill_time)
              ORDER BY year ASC
              LIMIT 15";
$weeklyCategoryRevenue = getCategoryRevenue($link, $weeklySQL);

$pdf->ChapterTitle('Weekly Revenue Breakdown');
$header = array('Date', 'Week', 'Revenue (BDT)');
$data = array();
while ($row = mysqli_fetch_assoc($weeklyCategoryRevenue)) {
    $data[] = array($row['year'], $row['week'], $row['weekly_category_revenue']);
}
$pdf->CustomTableThreeColumn($header, $data);

$pdf->Ln();

$monthlySQL = "SELECT CONCAT(YEAR(Bills.bill_time), '-', MONTH(Bills.bill_time)) AS year, MONTH(Bills.bill_time) AS month, SUM(Bill_Items.quantity * Menu.item_price) AS monthly_category_revenue
               FROM Bills
               JOIN Bill_Items ON Bills.bill_id = Bill_Items.bill_id
               JOIN Menu ON Bill_Items.item_id = Menu.item_id
               GROUP BY YEAR(Bills.bill_time), MONTH(Bills.bill_time)
               ORDER BY year ASC
               LIMIT 15";
$monthlyCategoryRevenue = getCategoryRevenue($link, $monthlySQL);

$pdf->ChapterTitle('Monthly Revenue Breakdown');
$header = array('Date', 'Month', 'Revenue (BDT)');
$data = array();
while ($row = mysqli_fetch_assoc($monthlyCategoryRevenue)) {
    $data[] = array($row['year'], $row['month'], $row['monthly_category_revenue']);
}
$pdf->CustomTableThreeColumn($header, $data);

$pdf->Ln();

$yearlySQL = "SELECT YEAR(Bills.bill_time) AS year, SUM(Bill_Items.quantity * Menu.item_price) AS yearly_category_revenue
              FROM Bills
              JOIN Bill_Items ON Bills.bill_id = Bill_Items.bill_id
              JOIN Menu ON Bill_Items.item_id = Menu.item_id
              GROUP BY YEAR(Bills.bill_time)
              ORDER BY year ASC
              LIMIT 15";
$yearlyCategoryRevenue = getCategoryRevenue($link, $yearlySQL);

$pdf->ChapterTitle('Yearly Revenue Breakdown');
$header = array('Date', 'Revenue (BDT)');
$data = array();
while ($row = mysqli_fetch_assoc($yearlyCategoryRevenue)) {
    $data[] = array($row['year'], $row['yearly_category_revenue']);
}
$pdf->CustomTableThreeColumn($header, $data);

$pdf->Output('RevenueReport.pdf', 'D');
?>