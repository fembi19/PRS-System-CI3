<?php if ($this->session->flashdata('success')) : echo "<script> Swal.fire({icon: 'success',title: 'Berhasil...!',text: '" . $this->session->flashdata('success') . "',showConfirmButton: false,timer: 4000});</script>";
elseif ($this->session->flashdata('error')) : echo "<script> Swal.fire({icon: 'error',title: 'Maaf...!',text: '" . $this->session->flashdata('error') . "',showConfirmButton: false,timer: 4000});</script>";
endif; ?>

<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      Manage
      <small>Voucher</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Voucher</li>
    </ol>
  </section>

  <section class="content">

    <div class="box box-primary box-solid">
      <div class="box-header with-border">
        <h3 class="box-title"><b>Data penggunaan Voucher</b></h3>

        <div class="box-tools pull-right">
          <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
          </button>
        </div>
        <!-- /.box-tools -->
      </div>

      <div class="box-body">

        <div style="position:relative;z-index: 9; margin:10px;">
          <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown">Export <span class="fa fa-caret-down"></span>
          </button>
          <ul class="dropdown-menu">
            <li><a onclick='jadi(1)' href="#">Excel</a></li>
            <li><a onclick='jadi(2)' href="#">PDF</a></li>
          </ul>
        </div>

        <div class="group" style="float: right;margin-top: -36px;position:relative;z-index: 999;">
          <input type="text" id="jadi" onchange="ubah()" name="datefilter" required>
          <span class="highlight"></span>
          <span class="bar"></span>
          <label class="label">Pilih Tanggal</label>
        </div>
        <hr>
        <div>
          <table id="manageTable" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th style="text-align: center;  width: 50px; ">Terpakai</th>
                <th style="text-align: center; ">ID Voucher</th>
                <th style="text-align: center;  ">Nama Voucher</th>
                <th style="text-align: center;  ">Outlet</th>
                <th style="text-align: center;  ">Tgl</th>
              </tr>
            </thead>

          </table>

        </div>
      </div>
      <div id="load" class="overlay">
        <i class="fa fa-refresh fa-spin"></i>
      </div>
    </div>

</div>
</section>
</div>

<script type="text/javascript">
  $(document).ready(function() {
    $('#userTable').DataTable();

    $("#mainvoucherNav").addClass('active');
    $("#viewvoucherNav").addClass('active');
  });
  var manageTable;
  var base_url = "<?php echo base_url(); ?>";

  $(function() {

    $('input[name="datefilter"]').daterangepicker({
      locale: {
        "format": 'DD/MM/YYYY',
        "applyLabel": 'Simpan',
        "cancelLabel": 'Hapus',
        "opens": "left",
        "drops": "up"
      },
      startDate: moment().subtract(30, 'days'),
      endDate: moment().subtract(1, 'days')
    });

    $('input[name="datefilter"]').on('apply.daterangepicker', function(ev, picker) {
      $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
    });

    $('input[name="datefilter"]').on('cancel.daterangepicker', function(ev, picker) {
      $(this).val('');
    });

  });

  function ubah() {
    var da = $('#jadi').val();
    $(document).ready(function() {
      manageTable = $('#manageTable').DataTable({
        processing: false,
        serverSide: false,
        destroy: true,
        "ajax": {
          url: base_url + 'voucher/fetchData',
          type: "POST",
          beforeSend: function() {
            $("#load").css("display", "block");
          },
          data: {
            tgl: da
          },
          complete: function() {
            $("#load").css("display", "none");
          }
        }
      });
    });

  }

  function jadi(id) {
    var da = $('#jadi').val();
    if (id === 1) {
      $.ajax({
        type: 'POST',
        data: {
          tgl: da,
          id: id
        },
        xhrFields: {
          responseType: 'blob'
        },
        url: 'voc_peg/laporan',
        success: function(data) {
          var a = document.createElement('a');
          var url = window.URL.createObjectURL(data);
          a.href = url;
          a.download = 'Voucher PRS ' + da + '.xlsx';
          document.body.append(a);
          a.click();
          a.remove();
          window.URL.revokeObjectURL(url);
        }
      });
    } else {
      alert('pdf');
    }
  }

  function reset(params) {
    Swal.fire({
      title: 'Yakin ingin reset?',
      text: "Pastikan dengan benar",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Reset'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'voc_peg/reset';
      }
    })
  };
</script>

<style>
  /* form starting stylings ------------------------------- */
  .group {
    position: relative;
    margin: 10px;
  }

  input {
    font-size: 18px;
    padding: 10px 10px 10px 5px;
    display: block;
    width: 194px;
    border: none;
    border-bottom: 1px solid #3c8dbc;
  }

  input:focus {
    outline: none;
  }

  .label {
    color: #3c8dbc;
    font-size: 18px;
    position: absolute;
    pointer-events: none;
    top: 10px;
    transition: 0.2s ease all;
    -moz-transition: 0.2s ease all;
    -webkit-transition: 0.2s ease all;
  }

  /* active state */
  input:focus~.label,
  input:valid~.label {
    top: -10px;
    font-size: 14px;
    color: #3c8dbc;
  }

  /* BOTTOM BARS ================================= */
  .bar {
    position: relative;
    display: block;
    width: 194px;
  }

  .bar:before,
  .bar:after {
    content: '';
    height: 2px;
    width: 0;
    bottom: 1px;
    position: absolute;
    background: #3c8dbc;
    transition: 0.2s ease all;
    -moz-transition: 0.2s ease all;
    -webkit-transition: 0.2s ease all;
  }

  .bar:before {
    left: 50%;
  }

  .bar:after {
    right: 50%;
  }

  /* active state */
  input:focus~.bar:before,
  input:focus~.bar:after {
    width: 50%;
  }

  /* HIGHLIGHTER ================================== */
  .highlight {
    position: absolute;
    height: 60%;
    width: 100px;
    top: 25%;
    left: 0;
    pointer-events: none;
    opacity: 0.5;
  }

  /* active state */
  input:focus~.highlight {
    -webkit-animation: inputHighlighter 0.3s ease;
    -moz-animation: inputHighlighter 0.3s ease;
    animation: inputHighlighter 0.3s ease;
  }

  /* ANIMATIONS ================ */
  @-webkit-keyframes inputHighlighter {
    from {
      background: #5264AE;
    }

    to {
      width: 0;
      background: transparent;
    }
  }

  @-moz-keyframes inputHighlighter {
    from {
      background: #5264AE;
    }

    to {
      width: 0;
      background: transparent;
    }
  }

  @keyframes inputHighlighter {
    from {
      background: #5264AE;
    }

    to {
      width: 0;
      background: transparent;
    }
  }
</style>