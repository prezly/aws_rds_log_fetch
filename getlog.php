<?php
require 'vendor/autoload.php';

date_default_timezone_set('UTC');

use Aws\Rds\RdsClient;
use pointybeard\ShellArgs\Lib;
use pointybeard\ShellArgs\Lib\Argument;
use pointybeard\ShellArgs\Lib\ArgumentIterator;

// checking script arguments
if (!getArgument('dbidentifier')) {
    print "Missing script parameter --dbidentifier\n";
    die();
}

if (!getArgument('region')) {
    print "Missing script parameter --region\n";
    die();
}

if (!getArgument('key')) {
    print "Missing script parameter --key\n";
    die();
}

if (!getArgument('secret')) {
    print "Missing script parameter --secret\n";
    die();
}

$dbidentifier = getArgument('dbidentifier');

$instance = new RdsClient([
    'key'     => getArgument('key'),
    'secret'  => getArgument('secret'),
    'region'  => getArgument('region'),
    'version' => '2014-10-31'
]);

$logfiles = $instance->describeDBLogFiles(['DBInstanceIdentifier' => $dbidentifier]);

$now = new DateTime();

$all = true;
//$all = false;

foreach ($logfiles->get('DescribeDBLogFiles') as $logfile) {
    $logfile_date = substr($logfile['LogFileName'],21,10);
    $logfile_hour = substr($logfile['LogFileName'],32,2);
    $logfile_date_hour = $logfile_date . " " . $logfile_hour;

    $output_to_file = $logfile['LogFileName'] . '.log';

    print "Log file: {$logfile['LogFileName']}\n";

    if (getArgument('clearfiles') && file_exists($output_to_file)) {
        print "Deleting file {$output_to_file}\n";

        unlink($output_to_file);
    }

    if (getArgument('lasthour')) {
        $one_hour_ago = new DateTime('-1hour');

        if ($logfile_date_hour != $one_hour_ago->format('Y-m-d H')) {
            //print "\tSkipping files other then last hour\n";
            continue;
        } elseif (file_exists($output_to_file)) {
            unlink($output_to_file);
        }
    }

    if ($logfile_date_hour == $now->format('Y-m-d H')) {
        print "\tNot writing file as hour has not passed yet\n";
        continue;
    }

    if (file_exists($output_to_file)) {
        print "\tNot writing file because it already exists\n";
        continue;
    }

    $has_more_data = true;
    $file_marker = 0;
    while ($has_more_data) {
        $log_file_description = $instance->downloadDBLogFilePortion([
                    'DBInstanceIdentifier' => $dbidentifier,
                    'LogFileName'          => $logfile['LogFileName'],
                    'Marker'               => $file_marker
        ]);

        print "\tMarker at {$log_file_description->get('Marker')}\n";

        $file_marker = $log_file_description->get('Marker');

        if ($log_file_description->get('AdditionalDataPending') == false) {
            $has_more_data = false;
        }

        print "\tWriting to file {$output_to_file}\n";
        file_put_contents($output_to_file, $log_file_description->get('LogFileData'), FILE_APPEND | LOCK_EX);

        if (getArgument('append_to_file')) {
            print "\tAppending to file ".getArgument('append_to_file')."\n";
            file_put_contents(getArgument('append_to_file'), $log_file_description->get('LogFileData'), FILE_APPEND | LOCK_EX);
        }
    }
}


function getArgument($argument) {
    $args = new ArgumentIterator();

    /* @var $argument_identifier Argument */
    $argument_identifier = $args->find($argument);

    if (!$argument_identifier) return null;

    if ($argument_identifier->__get('value'))
        return $argument_identifier->__get('value');

    return null;
}