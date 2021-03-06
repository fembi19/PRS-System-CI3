<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>PRS System | LAPORAN INVENTARIS <?php echo $store ?></title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>/assets/bower_components/bootstrap/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/bower_components/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>/assets/dist/css/AdminLTE.min.css">
</head>

<body onload="window.print();">
    <div class="box">
        <div class="box-header">
            <center><img src="https://wellthefood.com/wp-content/uploads/2020/08/prs.png" width="200">
                <h2 class="box-title" style="width: 100%;">
                    <center>
                        <font style="font-size: x-large;font-weight: 700;">CV. Prima Rasa Selaras</font>
                    </center>
                    <hr> <br><B>LAPORAN PRODUK LOGISTIK</B>

                </h2><br>
                <?php
                echo $title;
                ?>
            </center>
        </div>

        <!-- /.box-header -->
        <div class="box-body">


            <br><br>
            <h4><b>1.) Laporan Barang Keluar</b></h4>
            <?php
            
            //perualangan untuk outlet
            foreach ($hasil as $s) {

                $no = 0;

                $outlet = $this->model_stores->getStoresData($s['store_id']);

                echo '<h1>'. $outlet['name'].'</h1><br>
                    <table id="manageTable" class="table table-bordered table-striped">
                    <tr>
                    <th><center>No</center></th>
                    <th><center>Outlet</center></th>
                    <th><center>Nama Produk</center></th>
                    <th><center>Hrg/Satuan</center></th>
                    <th><center>Qty</center></th>
                    <th><center>Total Harga</center></th>
                    </tr>';

                $store_id = $this->session->userdata('store_id');
                
                
                
                $product = $this->model_orders->cetakpertanggalproduct($s['store_id'], $tgl_awal, $tgl_akhir);
                
                $qty=0;
                if ($product) {
                    
                    
                    foreach ($product as $row) {
                    
                        
                    $dataprodut = $this->model_orders->cetakpertanggal($row['product_id'],$s['store_id'], $tgl_awal, $tgl_akhir);

                    $jumlah_qtydeliv = 0;
                    if($dataprodut[0]['rate']){
                    $jumlah_rate = $dataprodut[0]['rate'];
                    }else{
                    $jumlah_rate = 0;
                    }
                    $satuannya = $dataprodut[0]['satuan'];
                    foreach ($dataprodut as $v) {
                        $jumlah_qtydeliv +=$v['qtydeliv'];
                    };
                    $jumlah_total = $jumlah_qtydeliv * $jumlah_rate;
                    $qty += $jumlah_total;
                    
                        $products = $this->model_products->getProductData($row['product_id']);
                        if($products){
                        $pp = $products['name'];
                        }else{
                            $pp = 'TAK DITEMUKAN';
                        }
                        
                        $no++;
            ?>

                        <tr>
                            <td>
                                <center><?php echo $no ?></center>
                            </td>
                            <td>
                                <center><?php echo $outlet['name']; ?></center>
                            </td>
                            <td><?php echo $pp; ?></td>
                            <td style="text-align: right;">Rp. <?php echo number_format($jumlah_rate, 0, ',', '.') . '/' . $satuannya; ?></td>
                            <td style="text-align: right;"><?php echo $jumlah_qtydeliv ?></td>
                            <td style="text-align: right;">Rp. <?php echo number_format($jumlah_total, 0, ',', '.'); ?></td>
                        </tr>

            <?php
                    }

                    echo  '<tr style="background-color: #00c0ef38;">
              <td colspan="5"><b>Jumlah</b></td>
              <td  style="text-align: right;"><b>Rp. ' . number_format($qty, 0, ',', '.') . '</b></td>
              </tr>';
                } else {
                    echo  '<tr style="background-color: #f443368a;">
                    <td colspan="6"><Center><b>Tidak ditemukan data</b></center></td>
                    </tr>';
                }
                echo ' </table><br><br>';
            }

            ?>


            <h4><b>2.) Laporan Barang Masuk Logistik</b></h4>

            <table id="manageTable" class="table table-bordered table-striped">

                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Qty Sebelumnya</th>
                    <th>Qty Masuk</th>
                    <th>Qty Total</th>
                    <th>Harga</th>
                    <th>Total Harga</th>
                </tr>

                <?php

                $no = 0;
                if ($hasilm) {

                    foreach ($hasilm as $row) {

                        $uang = $row->price;
                        $harga = number_format("$uang", 0, ",", ".");

                        $angka = $row->price * $row->qtytotal;
                        $hargatotal = number_format("$angka", 0, ",", ".");



                        $no++;
                ?>

                        <tr>
                            <td><?php echo $no ?></td>
                            <td><?php echo $row->tgl_bmasuk; ?></td>
                            <td><?php echo $row->sku; ?></td>
                            <td><?php echo $row->name; ?></td>
                            <td><?php echo $row->qtysblm; ?>/<?php echo $row->satuan; ?></td>
                            <td><?php echo $row->qtymasuk ?>/<?php echo $row->satuan; ?></td>
                            <td><?php echo $row->qtytotal; ?>/<?php echo $row->satuan; ?></td>
                            <td>Rp. <?php echo $harga ?></td>
                            <td>Rp. <?php echo $hargatotal ?></td>
                        </tr>

                <?php
                    }
                } else {
                    echo  '<tr style="background-color: #f443368a;">
                <td colspan="9"><Center><b>Tidak ditemukan data</b></center></td>
                </tr>';
                }

                ?>
            </table><br><br>

            <!--
            <h4><b>3.) Laporan Sisa Stock Logistik</b></h4>
            <table id="manageTable" class="table table-bordered table-striped">

                <tr>
                    <th style="width: 20px;">No</th>
                    <th>Nama Produk</th>
                    <th>Hrg/Satuan</th>
                    <th>Qty Sisa</th>
                    <th>Total Harga</th>
                </tr>
                <?php
                $result = array('data' => array());
                $no = 0;
                $data = $this->model_products->getProductData();

                $total = 0;
                $hrgtotal = 0;
                foreach ($data as $key => $value) {
                    $uang = $value['price'];
                    $harga = number_format("$uang", 0, ",", ".");

                    $angka = $value['price'] * $value['qty'];
                    $total += $angka;
                    $hrgtotal += $value['qty'];
                    $hargatotal = number_format("$angka", 0, ",", ".");
                    $no++;
                ?>
                    <tr>
                        <td><?php echo $no ?></td>
                        <td><?php echo $value['name'] ?></td>
                        <td>Rp. <?php echo $harga ?> / <?php echo $value['satuan'] ?></td>
                        <td><?php echo $value['qty'] ?></td>
                        <td>Rp. <?php echo $hargatotal ?></td>
                    </tr>

                <?php
                } // /foreach
                echo '<tr>
                        <td colspan="3"><b>Jumlah</b></td>
                        <td><b>' . $hrgtotal . '</b></td>
                        <td><b>Rp.' . $total . '</b></td>
                    </tr>';
                ?>

            </table>
-->

        </div>
    </div>
    </section>
    <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
</body>

</html>