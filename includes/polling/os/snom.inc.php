<?php

use LibreNMS\RRD\RrdDefinition;

echo "Polling SNOM device...\n";

// Get SNOM specific version string from silly SNOM location. Silly SNOM!
// FIXME - This needs a good cleanup...
$cmd = 'snmpget -O qv '.snmp_gen_auth($device).' '.$device['hostname'].':'.$device['port'].' 1.3.6.1.2.1.7526.2.4';
$poll_device['sysDescr']             = `$cmd`;
$poll_device['sysDescr']             = str_replace('-', ' ', $poll_device['sysDescr']);
$poll_device['sysDescr']             = str_replace('"', '', $poll_device['sysDescr']);
list($hardware, $features, $version) = explode(' ', $poll_device['sysDescr']);

// Get data for calls and network from SNOM specific SNMP OIDs.
$cmda = 'snmpget -O qv '.snmp_gen_auth($device).' '.$device['hostname'].':'.$device['port'].' 1.3.6.1.2.1.7526.2.1.1 1.3.6.1.2.1.7526.2.1.2 1.3.6.1.2.1.7526.2.2.1 1.3.6.1.2.1.7526.2.2.2';
$cmdb = 'snmpget -O qv '.snmp_gen_auth($device).' '.$device['hostname'].':'.$device['port'].' 1.3.6.1.2.1.7526.2.5 1.3.6.1.2.1.7526.2.6';
// echo($cmda);
$snmpdata  = `$cmda`;
$snmpdatab = `$cmdb`;

list($rxbytes, $rxpkts, $txbytes, $txpkts) = explode("\n", $snmpdata);
list($calls, $registrations)               = explode("\n", $snmpdatab);
$txbytes = (0 - $txbytes * 8);
$rxbytes = (0 - $rxbytes * 8);
echo "$rxbytes, $rxpkts, $txbytes, $txpkts, $calls, $registrations";

$rrd_name = 'data';
$rrd_def = RrdDefinition::make()
    ->addDataset('INOCTETS', 'COUNTER', null, 100000000000)
    ->addDataset('OUTOCTETS', 'COUNTER', null, 10000000000)
    ->addDataset('INPKTS', 'COUNTER', null, 10000000000)
    ->addDataset('OUTPKTS', 'COUNTER', null, 10000000000)
    ->addDataset('CALLS', 'COUNTER', null, 10000000000)
    ->addDataset('REGISTRATIONS', 'COUNTER', null, 10000000000);

$fields = array(
    'INOCTETS'      => $rxbytes,
    'OUTOCTETS'     => $txbytes,
    'INPKTS'        => $rxpkts,
    'OUTPKTS'       => $rxbytes,
    'CALLS'         => $calls,
    'REGISTRATIONS' => $registrations,
);

$tags = compact('rrd_name', 'rrd_def');
data_update($device, 'snom-data', $tags, $fields);
