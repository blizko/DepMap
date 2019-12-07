<?php
/*
 * Dependency Visualisation logic:
 * Copyright (c) 2018 Arturs Plisko <https://github.com/blizko http://www.blizko.lv>
 *
 * Based on LibreNMS Map module
 * Copyright (c) 2014 Neil Lathwood <https://github.com/laf/ http://www.lathwood.co.uk/fa>
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE at the top level of
 * the source code distribution for details.
 */
 
 ?>
<?php
$sql_array= array();
$join_sql="";

$devices_by_id = array();
$links = array();
$devices = array();

     $devices = dbFetchRows("SELECT
                             `M`.`child_device_id` AS `local_device_id`,
                             `D1`.`os` AS `local_os`,
                             `D1`.`hostname` AS `local_hostname`,
                             `D1`.`sysName` AS `local_sysName`,
                             `M`.`parent_device_id` AS `remote_device_id`,
                             `D2`.`os` AS `remote_os`,
                             `D2`.`hostname` AS `remote_hostname`,
                             `D2`.`sysName` AS `remote_sysName`
                      FROM `device_relationships` AS `M`
                             INNER JOIN `devices` AS `D1` ON `M`.`child_device_id`=`D1`.`device_id`
                             INNER JOIN `devices` AS `D2` ON `M`.`parent_device_id`=`D2`.`device_id`
							 $join_sql                    
					",$sql_array);
					  
// Iterating through found devices
foreach ($devices as $items) {
    $local_device = array('device_id'=>$items['local_device_id'], 'os'=>$items['local_os'], 'hostname'=>$items['local_hostname']);
    $remote_device = array('device_id'=>$items['remote_device_id'], 'os'=>$items['remote_os'], 'hostname'=>$items['remote_hostname']);

    $local_device_id = $items['local_device_id'];
    if (!array_key_exists($local_device_id, $devices_by_id)) {
        $items['sysName'] = $items['local_sysName'];
		// Note - Does not create device link within LibreNMS to allow "onclick" events on the graph
        $devices_by_id[$local_device_id] = array('id'=>$local_device_id,'label'=>shorthost(format_hostname($items, $items['local_hostname']), 1),'title'=>generate_device_link($local_device, '', array(), '', '', '', 0),'shape'=>'box');
    }
	$remote_device_id = $items['remote_device_id'];
	if (!array_key_exists($remote_device_id, $devices_by_id)) {
        $items['sysName'] = $items['remote_sysName'];
		// Note - Does not create device link within LibreNMS to allow "onclick" events on the graph
        $devices_by_id[$remote_device_id] = array('id'=>$remote_device_id,'label'=>shorthost(format_hostname($items, $items['remote_hostname']), 1),'title'=>generate_device_link($local_device, '', array(), '', '', '', 0),'shape'=>'box');
    }
	$width = 3;
	// Add directed edge between Parent and Child device
	$links[] = array('from'=>$items['remote_device_id'],'to'=>$items['local_device_id'],'title'=>"title",'width'=>$width,'arrows'=>"to");

}
$nodes = json_encode(array_values($devices_by_id));
$edges = json_encode($links);

if (count($devices_by_id) > 1 && count($links) > 0) {
?>
 
<div id="visualization"></div>
<script src="js/vis.min.js"></script>
<script type="text/javascript">
var height = $(window).height() - 100;
$('#visualization').height(height + 'px');
    // create an array with nodes
    var nodes =
<?php
echo $nodes;
?>
    ;
 
    // create an array with edges
    var edges =
<?php
echo $edges;
?>
    ;
    // create a network
    var container = document.getElementById('visualization');
    var data = {
        nodes: nodes,
        edges: edges,
        stabilize: true
    };
    var options =  <?php echo isset($config['network_map_vis_options']) ? $config['network_map_vis_options'] : '{}'; ?>;
    var network = new vis.Network(container, data, options);
    network.on('click', function (properties) {
    });
</script>

<?php
} else {
    print_message("No map to display, this may be because you don't have any defined device dependencies.");
}

$pagetitle[] = "Dependency Map";

?>
