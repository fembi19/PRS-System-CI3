<?php if ($this->session->flashdata('success')) :

  echo "<script> Swal.fire({
              icon: 'success',
              title: 'Berhasil...!',
              text: '" . $this->session->flashdata('success') . "',
              showConfirmButton: false,
              timer: 4000
            });</script>";

?>
<?php elseif ($this->session->flashdata('error')) :
  echo "<script> Swal.fire({
              icon: 'error',
              title: 'Maaf...!',
              text: '" . $this->session->flashdata('error') . "',
              showConfirmButton: false,
              timer: 4000
            });</script>";

?>
<?php endif; ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      Tambah
      <small>Belanja</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Belanja</li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
      <div class="col-md-12 col-xs-12">

        <div id="messages"></div>


        <div class="box box-success box-solid">
          <div class="box-header with-border">
            <h3 class="box-title"><b>Tambah Belanja</b></h3>

            <div class="box-tools pull-right">
              <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
              </button>
            </div>
            <!-- /.box-tools -->
          </div>
          <!-- /.box-header -->
          <form role="form" action="<?php echo base_url('belanja/create') ?>" method="post" class="form-horizontal">
            <div class="box-body">

              <?php echo validation_errors(); ?>

              <div class="col-md-4 col-xs-12 pull pull-left">
                <div class="form-group">
                  <label class="col-sm-5 control-label" style="text-align:left;">Tanggal :</label>
                  <div class="col-sm-7">

                    <div class="input-group date">
                      <div class="input-group-addon ">
                        <i class="fa fa-calendar"></i>
                      </div>
                      <input type="date" id="tgl" required name="tgl" class="form-control pull-right">
                    </div>

                  </div>
                </div>
              </div>

              <table class="table table-bordered" id="product_info_table" style="overflow-x: scroll;display:block;">
                <thead>
                  <th style="width:50%;min-width:200px;text-align: center;">Produk</th>
                  <th style="width:10%;min-width:100px;text-align: center;">Rp/1</th>
                  <th style="width:10%;min-width:70px;text-align: center;">Qty</th>
                  <th style="width:10%;min-width:70px;text-align: center;">Satuan</th>
                  <th style="width:20%;min-width:100px;text-align: center;">Jumlah</th>
                  <th style="width:10%"><button type="button" id="add_row" class="btn btn-default"><i class="fa fa-plus"></i></button></th>
                  </tr>
                </thead>

                <tbody>

                  <tr id="row_1">
                    <td>
                      <select class="form-control select_group product" data-row-id="row_1" id="product_1" name="product[]" style="width:100%;" onchange="getProductData(1)" required=''>
                        <option selected="true" disabled="disabled">Pilih Produk</option>
                        <?php foreach ($products as $k => $v) : ?>
                          <option value="<?php echo $v['id'] ?>"><?php echo $v['name'] ?></option>
                        <?php endforeach ?>
                      </select>
                    </td>
                    <td>
                      <input type="number" name="rate_value[]" id="rate_value_1" required class="form-control" autocomplete="off">
                    </td>
                    <td><input type="number" step="any" name="qty[]" id="qty_1" class="form-control" required onkeyup="getTotal(1)"></td>

                    <td>
                      <input type="text" name="satuan[]" id="satuan_1" class="form-control" disabled autocomplete="off">
                      <input type="hidden" name="satuan_value[]" id="satuan_value_1" class="form-control" autocomplete="off">
                    </td>
                    <td>
                      <input type="text" name="amount[]" id="amount_1" class="form-control" disabled autocomplete="off">
                      <input type="hidden" name="amount_value[]" id="amount_value_1" class="form-control" autocomplete="off">
                    </td>
                    <td><button type="button" class="btn btn-default" onclick="removeRow('1')"><i class="fa fa-close"></i></button></td>
                  </tr>
                </tbody>
              </table>
              <br /> <br />

              <div class="col-md-6 col-xs-12 pull pull-right">

                <div class="form-group">
                  <label for="gross_amount" class="col-sm-5 control-label">Jumlah Harga</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" id="gross_amount" name="gross_amount" disabled autocomplete="off">
                    <input type="hidden" class="form-control" id="gross_amount_value" name="gross_amount_value" autocomplete="off">
                  </div>
                </div>
                <?php if ($is_service_enabled == true) : ?>
                  <div class="form-group">
                    <label for="service_charge" class="col-sm-5 control-label">S-Charge <?php echo $company_data['service_charge_value'] ?> %</label>
                    <div class="col-sm-7">
                      <input type="text" class="form-control" id="service_charge" name="service_charge" disabled autocomplete="off">
                      <input type="hidden" class="form-control" id="service_charge_value" name="service_charge_value" autocomplete="off">
                    </div>
                  </div>
                <?php endif; ?>
                <?php if ($is_vat_enabled == true) : ?>
                  <div class="form-group">
                    <label for="vat_charge" class="col-sm-5 control-label">PPN <?php echo $company_data['vat_charge_value'] ?> %</label>
                    <div class="col-sm-7">
                      <input type="text" class="form-control" id="vat_charge" name="vat_charge" disabled autocomplete="off">
                      <input type="hidden" class="form-control" id="vat_charge_value" name="vat_charge_value" autocomplete="off">
                    </div>
                  </div>
                <?php endif; ?>
                <div class="form-group" style="display: none;">
                  <label for="discount" class="col-sm-5 control-label">Discount</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" id="discount" name="discount" placeholder="Discount" onkeyup="subAmount()" autocomplete="off">
                  </div>
                </div>
                <div class="form-group" style="display: none ;">
                  <label for="net_amount" class="col-sm-5 control-label">Jumlah Total</label>
                  <div class="col-sm-7">
                    <input type="text" class="form-control" id="net_amount" name="net_amount" disabled autocomplete="off">
                    <input type="hidden" class="form-control" id="net_amount_value" name="net_amount_value" autocomplete="off">
                  </div>
                </div>

              </div>
            </div>
            <!-- /.box-body -->

            <div class="box-footer">
              <input type="hidden" name="service_charge_rate" value="<?php echo $company_data['service_charge_value'] ?>" autocomplete="off">
              <input type="hidden" name="vat_charge_rate" value="<?php echo $company_data['vat_charge_value'] ?>" autocomplete="off">
              <button type="submit" class="btn btn-success"><i class="fa fa-sign-in"></i> Tambah</button>
              <a href="<?php echo base_url('belanja/') ?>" class="btn btn-warning"><i class="fa fa-close"></i> Batal</a>
              <a class="btn btn-primary" onclick="ambil()">Ambil</a>*Mengambil data dari order sesuai tanggal diatas dengan tgl laporan order data akan otomatis terakumulasi
            </div>
          </form>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <!-- col-md-12 -->
    </div>
    <!-- /.row -->


  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<script type="text/javascript">
  var base_url = "<?php echo base_url(); ?>";

  $(document).ready(function() {
    $(".select_group").select2();
    // $("#description").wysihtml5();

    $("#mainbelanjaNav").addClass('active');
    $("#addbelanjaNav").addClass('active');

    var btnCust = '<button type="button" class="btn btn-secondary" title="Add picture tags" ' +
      'onclick="alert(\'Call your custom code here.\')">' +
      '<i class="glyphicon glyphicon-tag"></i>' +
      '</button>';

    // Add new row in the table 
    $("#add_row").unbind('click').bind('click', function() {
      var table = $("#product_info_table");
      var count_table_tbody_tr = $("#product_info_table tbody tr").length;
      var row_id = count_table_tbody_tr + 1;

      var tambah = $('#add_row');

      $.ajax({
        url: base_url + '/orders/getTableProductRow/',
        type: 'post',
        dataType: 'json',
        beforeSend: function() {
          tambah.attr('disabled', 'disabled');
        },
        success: function(response) {
          tambah.attr('disabled', false);
          // console.log(reponse.x);
          var html = '<tr id="row_' + row_id + '">' +
            '<td>' +
            '<select class="form-control select_group product" data-row-id="' + row_id + '" id="product_' + row_id + '" name="product[]" style="width:100%;" onchange="getProductData(' + row_id + ')" required>' +
            '<option selected="true" disabled="disabled">Pilih Produk</option>';
          $.each(response, function(index, value) {
            html += '<option value="' + value.id + '">' + value.name + '</option>';
          });

          html += '</select>' +
            '</td>' +
            '<td><input type="number" name="rate_value[]" id="rate_value_' + row_id + '" class="form-control"></td>' +
            '<td><input type="number" step="any" name="qty[]" id="qty_' + row_id + '" class="form-control" onkeyup="getTotal(' + row_id + ')"></td>' +
            '<td><input type="text" name="satuan[]" id="satuan_' + row_id + '" class="form-control" disabled><input type="hidden" name="satuan_value[]" id="satuan_value_' + row_id + '" class="form-control"></td>' +
            '<td><input type="text" name="amount[]" id="amount_' + row_id + '" class="form-control" disabled><input type="hidden" name="amount_value[]" id="amount_value_' + row_id + '" class="form-control"></td>' +
            '<td><button type="button" class="btn btn-default" onclick="removeRow(\'' + row_id + '\')"><i class="fa fa-close"></i></button></td>' +
            '</tr>';

          if (count_table_tbody_tr >= 1) {
            $("#product_info_table tbody tr:last").after(html);
          } else {
            $("#product_info_table tbody").html(html);
          }

          $(".product").select2();

        }
      });

      return false;
    });

  }); // /document

  function getTotal(row = null) {
    if (row) {
      var total = Number($("#rate_value_" + row).val()) * Number($("#qty_" + row).val());

      total = total.toFixed(0);
      $("#amount_" + row).val(total);
      $("#amount_value_" + row).val(total);

      subAmount();

    } else {
      alert('no row !! please refresh the page');
    }
  }

  // get the product information from the server
  function getProductData(row_id) {
    var product_id = $("#product_" + row_id).val();
    if (product_id == "") {
      $("#rate_value_" + row_id).val("");

      $("#satuan_" + row_id).val("");
      $("#satuan_value_" + row_id).val("");

      $("#nama_produk_" + row_id).val("");

      $("#qty_" + row_id).val("");

      $("#amount_" + row_id).val("");
      $("#amount_value_" + row_id).val("");

    } else {
      $.ajax({
        url: base_url + 'orders/getProductValueById',
        type: 'post',
        data: {
          product_id: product_id
        },
        dataType: 'json',
        success: function(response) {
          // setting the rate value into the rate input field

          $("#rate_value_" + row_id).val(response.price);

          $("#satuan_" + row_id).val(response.satuan);
          $("#satuan_value_" + row_id).val(response.satuan);

          $("#nama_produk_" + row_id).val(response.name);

          $("#qty_" + row_id).val(1);
          $("#qty_value_" + row_id).val(1);

          var total = Number(response.price) * 1;
          total = total.toFixed(0);
          $("#amount_" + row_id).val(total);
          $("#amount_value_" + row_id).val(total);

          subAmount();
        } // /success
      }); // /ajax function to fetch the product data 
    }
  }

  // calculate the total amount of the order
  function subAmount() {
    var service_charge = <?php echo ($company_data['service_charge_value'] > 0) ? $company_data['service_charge_value'] : 0; ?>;
    var vat_charge = <?php echo ($company_data['vat_charge_value'] > 0) ? $company_data['vat_charge_value'] : 0; ?>;

    var tableProductLength = $("#product_info_table tbody tr").length;
    var totalSubAmount = 0;
    for (x = 0; x < tableProductLength; x++) {
      var tr = $("#product_info_table tbody tr")[x];
      var count = $(tr).attr('id');
      count = count.substring(4);

      totalSubAmount = Number(totalSubAmount) + Number($("#amount_" + count).val());
    } // /for

    totalSubAmount = totalSubAmount.toFixed(0);

    // sub total
    $("#gross_amount").val(totalSubAmount);
    $("#gross_amount_value").val(totalSubAmount);

    // vat
    var vat = (Number($("#gross_amount").val()) / 100) * vat_charge;
    vat = vat.toFixed(0);
    $("#vat_charge").val(vat);
    $("#vat_charge_value").val(vat);

    // service
    var service = (Number($("#gross_amount").val()) / 100) * service_charge;
    service = service.toFixed(0);
    $("#service_charge").val(service);
    $("#service_charge_value").val(service);

    // total amount
    var totalAmount = (Number(totalSubAmount) + Number(vat) + Number(service));
    totalAmount = totalAmount.toFixed(0);
    // $("#net_amount").val(totalAmount);
    // $("#totalAmountValue").val(totalAmount);

    var discount = $("#discount").val();
    if (discount) {
      var grandTotal = Number(totalAmount) - Number(discount);
      grandTotal = grandTotal.toFixed(0);
      $("#net_amount").val(grandTotal);
      $("#net_amount_value").val(grandTotal);
    } else {
      $("#net_amount").val(totalAmount);
      $("#net_amount_value").val(totalAmount);

    } // /else discount 

  } // /sub total amount

  function removeRow(tr_id) {
    $("#product_info_table tbody tr#row_" + tr_id).remove();
    subAmount();
  }

  function ambil() {
    var tgl = $('#tgl').val();
    if (tgl) {

      $.ajax({
        url: "<?= base_url('belanja/ambil/') ?>",
        type: 'POST',
        data: {
          tgl: tgl
        },
        success: function(data) {
          if (data > 0) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil...!',
              text: 'Data berhasil diambil',
              showConfirmButton: false,
              timer: 2000
            });
            setTimeout(function() {
              window.location.href = "<?= base_url("belanja/edit/") ?>" + data;
            }, 2000)
          } else if (data == 'zz') {
            Swal.fire({
              icon: 'error',
              title: 'Gagal...!',
              text: 'Gagal membuat belanja',
              showConfirmButton: false,
              timer: 4000
            });
          } else if (data == 'z') {
            Swal.fire({
              icon: 'error',
              title: 'Gagal...!',
              text: 'Tanggal Telah Ada',
              showConfirmButton: false,
              timer: 4000
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Gagal...!',
              text: 'Terdapat Masalah',
              showConfirmButton: false,
              timer: 4000
            });

          }
        }
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Gagal...!',
        text: 'Tanggal Harus diisi',
        showConfirmButton: false,
        timer: 4000
      });
    }
  }
</script>