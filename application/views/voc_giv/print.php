<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Voucher</title>
</head>

<!-- <body onload="window.print();"> -->

<body>
	<style>
		a {
			text-decoration: none;
		}
	</style>
	<div style="
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;">

		<?php
		foreach ($akun as $key => $d) {
			if (!$d['claim']) :
				$asal = $this->model_vocgif->namavoc($d['asal']);
		?> <div style="margin: 5px;">
					<center>

						<img style="left: 128;top: -8;position: relative;" width="65" height="65" src="<?php echo base_url() ?>assets/data qr/<?php echo $d['kode'] ?>.png">
						<img width="180" height="315" src="<?php echo  base_url() ?>assets/voucher/<?php echo $asal['img']; ?>">

					</center>
				</div>
		<?php
			endif;
		}
		?>


	</div>
	<style type="text/css">
		body {
			font-size: 15px;
			color: #343d44;
			font-family: "segoe-ui", "open-sans", tahoma, arial;
			padding: 0;
			margin: 0;
		}

		table {
			margin: auto;
			font-family: "Lucida Sans Unicode", "Lucida Grande", "Segoe Ui";
			font-size: 12px;
		}

		h1 {
			margin: 25px auto 0;
			text-align: center;
			text-transform: uppercase;
			font-size: 17px;
		}

		table td {
			transition: all .5s;
		}

		/* Table */
		.data-table {
			border-collapse: collapse;
			font-size: 14px;
			min-width: 537px;
		}

		.data-table th,
		.data-table td {
			border: 1px solid #e1edff;
			padding: 7px 17px;
		}

		.data-table caption {
			margin: 7px;
		}

		/* Table Header */
		.data-table thead th {
			background-color: #508abb;
			color: #FFFFFF;
			border-color: #6ea1cc !important;
			text-transform: uppercase;
		}

		/* Table Body */
		.data-table tbody td {
			color: #353535;
		}

		.data-table tbody td:first-child,
		.data-table tbody td:nth-child(4),
		.data-table tbody td:last-child {
			text-align: right;
		}

		.data-table tbody tr:nth-child(odd) td {
			background-color: #f4fbff;
		}

		.data-table tbody tr:hover td {
			background-color: #ffffa2;
			border-color: #ffff0f;
		}

		/* Table Footer */
		.data-table tfoot th {
			background-color: #e5f5ff;
			text-align: right;
		}

		.data-table tfoot th:first-child {
			text-align: left;
		}

		.data-table tbody td:empty {
			background-color: #ffcccc;
		}

		textarea {
			width: 400px;
			min-height: 300px;
		}
	</style>
</body>

</html>