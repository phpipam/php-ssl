<input type="hidden" id="log_object" value="hosts">
<input type="hidden" id="log_object_id" value="<?php print $host->id; ?>">

<div class='row'>
	<div class='col-12' style='margin-top:10px;'>
		<div class='card'>
			<div class='card-header'><?php print $url_items["logs"]['icon']; ?> <?php print _("Host logs"); ?></div>
			<div>

				<table
					class="table table-hover align-top table-md"
					data-classes="table table-hover table-sm"
					id="table-host-logs"
					data-toggle="table"
					data-sortable="false"
					data-page-size="20"
					data-page-list="[10, 20, 50, 100]"
					data-ajax="ajaxRequestHostLogs"
					data-search="false"
					data-side-pagination="server"
					data-server-sort="true"
					data-pagination="true"
					data-loading-template="loadingHostMessage"
					data-remember-order="true"
					data-loading-font-size="14"
					data-sort-name="id"
					data-sort-order="desc"
				>
				<thead>
					<tr>
						<th data-field="id" data-sortable="true" data-width="20" data-width-unit="px" data-white-space="nowrap" >ID</th>
						<th data-field="user" data-sortable="true">User</th>
						<th data-field="date" data-sortable="true">Date</th>
						<th data-field="action" data-width="20" data-width-unit="px">Action</th>
						<th data-field="text" data-sortable="true">Content</th>
						<th data-field="diff" data-width="20" data-width-unit="px">Change</th>
					</tr>
				</thead>
				</table>

			</div>
		</div>
	</div>
</div>


<style type="text/css">
#table-host-logs tr td:nth-child(1) {
    white-space: nowrap !important;
}
</style>


<script>
window.ajaxRequestHostLogs = params => {
    var log_object    = $('#log_object').val();
    var log_object_id = $('#log_object_id').val();
    var data = params.data;
    data.object    = log_object;
    data.object_id = log_object_id;
    $.ajax({
        type: "POST",
        url: '/route/ajax/logs.php',
        data: data,
        dataType: "json",
        success: function (data) {
            params.success({
                "rows":  data.rows,
                "total": data.total
            })
        },
        error: function (er) {
            console.log(er);
            params.error(er);
        }
    });
}

function loadingHostMessage () {
  return '<span class="loading-wrap">' +
    '<span class="loading-text" style="font-size:14px;">Loading</span>' +
    '\t<span class="animation-wrap"><span class="animation-dot"></span></span>' +
    '</span>'
}
</script>
