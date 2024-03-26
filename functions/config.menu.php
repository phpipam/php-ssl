<?php


$url_items = [];

//
// Dahsboard
//
$url_items["dashboard"]["dashboard"] = ["title" => "Dashboard","icon" => "fa fa-table-columns"];

//
// Tenants
//
if($user->admin=="1")
$url_items["tenants"]["tenants"] = ["title" => "Tenants","icon" => "fa fa-users"];

//
// Navigation
//

// users
$url_items["navigation"]["users"] = ["title" => "Users","icon" => "fa fa-user"];

// Zones
$url_items["navigation"]["zones"] = ["title" => "Zones", "icon" => "fa fa-database"];

// Certificates
$url_items["navigation"]["certificates"] = ["title" => "Certificates","icon" => "fa fa-certificate"];
$url_items["navigation"]["certificates"]["submenu"]["list"]        = "All certificates";
// $url_items["navigation"]["certificates"]["submenu"]["hosts"]    = "Host view";
$url_items["navigation"]["certificates"]["submenu"]["expire_soon"] = "Expire soon";
$url_items["navigation"]["certificates"]["submenu"]["expired"]     = "Expired";
$url_items["navigation"]["certificates"]["submenu"]["orphaned"]    = "Orphaned";

// Blocked issuers
$url_items["navigation"]["ignored"] = ["title" => "Ignored issuers", "icon" => "fa fa-eye-slash"];

// Agents
$url_items["navigation"]["agents"] = ["title" => "Scan agents", "icon" => "fa fa-server"];

// Port groups
$url_items["navigation"]["portgroups"] = ["title" => "Port groups", "icon" => "fa fa-layer-group"];

// Cron
$url_items["navigation"]["cron"] = ["title" => "Cron jobs", "icon" => "fa fa-clock"];


//
// Search
//
$url_items["search"]["search"] = ["title" => "Search", "icon" => "fa fa-search"];


//
// Tools
//

// Fetch
$url_items["tools"]["fetch"]  	  = ["title" => "Fetch website cetificate", "icon" => "fa fa-certificate"];

// Transform
$url_items["tools"]["transform"]  = ["title" => "Transform certificate", "icon" => "fa fa-exchange"];

// $url_items["tools"]["import"] = ["title" => "Import","icon" => "fa fa-upload"];
// $url_items["tools"]["export"] = ["title" => "Export","icon" => "fa fa-download"];
// $url_items["tools"]["logs"]   = ["title" => "Logs","icon" => "fa fa-note-sticky"];