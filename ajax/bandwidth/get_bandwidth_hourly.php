<?php 

require '../../includes/csrf.php';

if (filter_input(INPUT_GET, 'tu') == 'h') {

    header('X-Content-Type-Options: nosniff');
    header('Content-Type: application/json');

    $data_template = array(
    0     => array('date' => '00:00', 'rx' => 0, 'tx' => 0),
    1     => array('date' => '01:00', 'rx' => 0, 'tx' => 0),
    2     => array('date' => '02:00', 'rx' => 0, 'tx' => 0),
    3     => array('date' => '03:00', 'rx' => 0, 'tx' => 0),
    4     => array('date' => '04:00', 'rx' => 0, 'tx' => 0),
    5     => array('date' => '05:00', 'rx' => 0, 'tx' => 0),
    6     => array('date' => '06:00', 'rx' => 0, 'tx' => 0),
    7     => array('date' => '07:00', 'rx' => 0, 'tx' => 0),
    8     => array('date' => '08:00', 'rx' => 0, 'tx' => 0),
    9     => array('date' => '09:00', 'rx' => 0, 'tx' => 0),
    10     => array('date' => '10:00', 'rx' => 0, 'tx' => 0),
    11     => array('date' => '11:00', 'rx' => 0, 'tx' => 0),
    12     => array('date' => '12:00', 'rx' => 0, 'tx' => 0),
    13     => array('date' => '13:00', 'rx' => 0, 'tx' => 0),
    14     => array('date' => '14:00', 'rx' => 0, 'tx' => 0),
    15     => array('date' => '15:00', 'rx' => 0, 'tx' => 0),
    16     => array('date' => '16:00', 'rx' => 0, 'tx' => 0),
    17     => array('date' => '17:00', 'rx' => 0, 'tx' => 0),
    18     => array('date' => '18:00', 'rx' => 0, 'tx' => 0),
    19     => array('date' => '19:00', 'rx' => 0, 'tx' => 0),
    20     => array('date' => '20:00', 'rx' => 0, 'tx' => 0),
    21     => array('date' => '21:00', 'rx' => 0, 'tx' => 0),
    22     => array('date' => '22:00', 'rx' => 0, 'tx' => 0),
    23     => array('date' => '23:00', 'rx' => 0, 'tx' => 0)
    );




    exec(sprintf('vnstat -i %s --json h', escapeshellarg($interface)), $jsonstdoutvnstat, $exitcodedaily);
    if ($exitcodedaily !== 0) {
        exit('vnstat error');
    }

    $jsonobj = json_decode($jsonstdoutvnstat[0], true)['interfaces'][0];
    $jsonData = $jsonobj['traffic']['hours'];
    for ($i = count($jsonData) - 1; $i >= 0; --$i) {
        $data_template[$jsonData[$i]['id']]['rx'] = round($jsonData[$i]['rx'] / 1024, 0);
        $data_template[$jsonData[$i]['id']]['tx'] = round($jsonData[$i]['tx'] / 1024, 0);
    }

    $data = array();
    $hour = $jsonobj['updated']['time']['hour'];
    foreach ($data_template as $key => $value) {
        if ($key > $hour) {
            array_push($data, $value);
        }
    }
    foreach ($data_template as $key => $value) {
        if ($key <= $hour) {
            array_push($data, $value);
        }
    }
    echo json_encode($data);
    exit(0);
}
