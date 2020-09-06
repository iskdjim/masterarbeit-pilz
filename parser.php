<?php

require_once __DIR__ . '/vendor/autoload.php';

use Phpml\Preprocessing\Normalizer;

if ( isset($_POST["submit"]) ) {

    if (isset($_FILES["file"])) {

        //if there was an error uploading the file
        if ($_FILES["file"]["error"] > 0) {
            echo "Return Code: " . $_FILES["file"]["error"] . "<br />";

        } else {


            $finalData = [];
            $dayParts = [];

            $date = new DateTime();
            $date->setTime(0, 0, 0);

            for ($i = 0; $i < 96; $i++) {
                $dayParts[$date->format('H:i:s')] = 0;
                $date->modify('+15 minutes');
            }

            $storagename = "data.csv";
            move_uploaded_file($_FILES["file"]["tmp_name"], "./files/" . $storagename);

            if ($_POST['type'] == "noise") {
                parseNoise($dayParts, $finalData);
            } elseif($_POST['type'] == "light") {
                parseLight($dayParts, $finalData);
            } else {
                parseAccelerometer($dayParts, $finalData);
            }

        }
    }
} else {
?>
    <table width="600">
    <form action="" method="post" enctype="multipart/form-data">
        <tr>
            <td width="20%">Type</td>
            <td width="80%">
                <select name="type">
                    <option value="noise">Noise</option>
                    <option value="accelerometer">Accelerometer</option>
                    <option value="light">Light</option>
                </select>
            </td>
        </tr>

        <tr>
            <td width="20%">Select file</td>
            <td width="80%"><input type="file" name="file" id="file" /></td>
        </tr>

        <tr>
            <td>Submit</td>
            <td><input type="submit" name="submit" /></td>
        </tr>

    </form>
</table>

<?php
}

function parseAccelerometer($dayParts, $finalData)
{
    $row = 1;
    $lastDate = null;

    if (($handle = fopen('./files/data.csv', "r")) !== FALSE) {

        while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
            $num = count($data);
            if ($row > 1) {
                if ($data[0]) {
                    $actualDate = new DateTime($data[0]);
                    if ($actualDate->format('Y-m-d') != $lastDate) {
                        $finalData[$actualDate->format('Y-m-d')] = $dayParts;
                    }


                    $roundedMinutes = roundToQuarterHour($actualDate->format('H:i') . ':00');

                    $roundedTime = $actualDate->setTime($actualDate->format('H'), $roundedMinutes, 0);

                    $finalData[$actualDate->format('Y-m-d')][$roundedTime->format('H:i:s')] = $data[1];

                }

                $lastDate = $actualDate->format('Y-m-d');
            }
            $row++;

        }
        fclose($handle);
    }

    generateOutputV2($dayParts, $finalData);
}

function parseNoise($dayParts, $finalData)
{

    $row = 1;
    $lastDate = null;
if (($handle = fopen('./files/data.csv', "r")) !== FALSE) {

    while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
            $num = count($data);

            if ($row > 1) {
                if ($data[3]) {
                    $actualDate = new DateTime($data[3]);
                    if ($actualDate->format('Y-m-d') != $lastDate) {
                        $finalData[$actualDate->format('Y-m-d')] = $dayParts;
                    }


                    $roundedMinutes = roundToQuarterHour($actualDate->format('H:i') . ':00');

                    $roundedTime = $actualDate->setTime($actualDate->format('H'), $roundedMinutes, 0);

                    $finalData[$actualDate->format('Y-m-d')][$roundedTime->format('H:i:s')] = $data[7];

                }

                $lastDate = $actualDate->format('Y-m-d');
            }
            $row++;

        }
        fclose($handle);
    }

    generateOutputV2($dayParts, $finalData);
}

function parseLight($dayParts, $finalData)
{

    $row = 1;
    $lastDate = null;
if (($handle = fopen('./files/data.csv', "r")) !== FALSE) {

    while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {
            $num = count($data);

            if ($row > 1) {
                if ($data[1]) {
                    $actualDate = new DateTime($data[1]);
                    if ($actualDate->format('Y-m-d') != $lastDate) {
                        $finalData[$actualDate->format('Y-m-d')] = $dayParts;
                    }


                    $roundedMinutes = roundToQuarterHour($actualDate->format('H:i') . ':00');

                    $roundedTime = $actualDate->setTime($actualDate->format('H'), $roundedMinutes, 0);

                    $finalData[$actualDate->format('Y-m-d')][$roundedTime->format('H:i:s')] = $data[5];

                }

                $lastDate = $actualDate->format('Y-m-d');
            }
            $row++;

        }
        fclose($handle);
    }

    generateOutputV2($dayParts, $finalData);
}

function roundToQuarterHour($timestring)
{
    $minutes = date('i', strtotime($timestring));
    return $minutes - ($minutes % 15);
}

function generateOutput($dayParts, $finalData)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="output.csv";');
    $fp = fopen('php://output', 'w');
    $headRow = ['date'];
    foreach ($dayParts as $time => $value) {
        $headRow[] = $time;
    }
    fputcsv($fp, $headRow);

    foreach ($finalData as $date => $values) {
        $row = [];
        $row[] = $date;
        foreach ($values as $time => $value) {
            $row[] = $value;
        }

        fputcsv($fp, $row);
    }

    fclose($fp);
}

function generateOutputV2($dayParts, $finalData)
{

    unlink('./files/data.csv');
    $normalizer = new Normalizer();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="output.csv";');
    $fp = fopen('php://output', 'w');
    $headRow = [''];
    foreach ($finalData as $date => $value) {
        $headRow[] = $date;
    }

    fputcsv($fp, $headRow);

    $timeDates = [];
    foreach ($dayParts as $time => $value) {
        $timeDates[$time] = [$time];
    }

    foreach ($finalData as $date => $values) {
        foreach ($values as $time => $value) {
            $timeDates[$time][] = $value;

        }

    }

    foreach ($dayParts as $time => $value) {
        $timeValuesWithDate = $timeDates[$time];
        array_shift($timeValuesWithDate);
        $normalizer->normalizeL1($timeValuesWithDate);
        fputcsv($fp, array_merge([$timeDates[$time][0]], $timeValuesWithDate));
    }

    fclose($fp);


}