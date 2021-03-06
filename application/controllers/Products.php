<?php

defined('BASEPATH') or exit('No direct script access allowed');

require('./assets/phpoffice/vendor/autoload.php');

require('./assets/fpdf/fpdf.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Products extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->not_logged_in();

        $this->data['page_title'] = 'Products';

        $this->load->model('model_products');
        $this->load->model('model_orders');
        $this->load->model('model_brands');
        $this->load->model('model_category');
        $this->load->model('model_stores');
        $this->load->model('model_attributes');
        $this->load->model('model_company');
        $this->load->model('model_belanja');
        $this->load->model('model_dapro');
    }

    public function index()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->data['store'] = $this->model_stores->getStoresoutlet();
        if (isset($_GET['filter'])) {
            $data = $this->model_stores->getStoresData($_GET['filter']);
            if ($data) {
                $this->data['storefilter'] = $data['name'];
            } else {
                $this->data['storefilter'] = $_GET['filter'];
            }
        } else {
            $this->data['storefilter'] = 'SEMUA';
        }
        $prdct = $this->model_products->getProductData();

        $hrgbr = [];
        $hrgskrng = [];
        foreach ($prdct as $key => $v) {

            $hrgitem = $this->model_belanja->getbelanjaterimabyallid(mktime(0, 0, 0, date("n"), date("j") + 7, date("Y")), date('Y-m-d'), $v['id']);

            $jmlhrg = 0;

            foreach ($hrgitem as $h) {
                $cekbelanja = $this->model_belanja->getbelanjaData($h['belanja_id']);
                if ($cekbelanja['status'] && $h['harga']) {
                    $jmlhrg += $h['harga'];
                }
            }

            if ($jmlrow = count($hrgitem)) {
                $jl = ceil($jmlhrg / $jmlrow); //jumlah
                $persen = $jl * 0.1;
                $hargacek = round($jl + $persen);
            } else {
                $hargacek = $jmlhrg;
            }

            $hrgbr[] = $hargacek;

            $hrgskrng[] = $v['price'];
        }

        $cekarray = 0;
        $cekin = '';
        foreach ($hrgskrng as $itung => $k) {
            if ($k != $hrgbr[$itung]) {
                $cekarray += 1;
            }
        }

        if (!$cekarray) {
            $this->data['updateharga'] = false;
        } else {
            $this->data['updateharga'] = true;
        }

        $this->data['products'] = $prdct;
        $this->render_template('products/index', $this->data);
    }

    public function fetchProductData()
    {
        $store_id = $this->session->userdata('store_id');
        $filter = $this->input->post('filter');
        $result = array('data' => array());

        if ($filter) {
            $data = $this->model_products->getProductDatatampil($store_id, $filter);
        } else {
            $data = $this->model_products->getProductDataGudang($store_id);
        }

        foreach ($data as $key => $value) {


            $buttons = ' <div class="btn-group dropleft">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"> <span class="caret"></span></button>
            <ul class="dropdown-menu">';
            if (in_array('updateProduct', $this->permission)) {
                $buttons .= '<li><a href="' . base_url('products/update/' . $value['id']) . '"><i class="fa fa-pencil"></i> Edit</a></li>';
            }

            if (in_array('deleteProduct', $this->permission)) {
                $buttons .= '<li><a style="cursor:pointer;" onclick="removeFunc(' . $value['id'] . ')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i> Hapus</a></li>';
            }
            $buttons .= '</ul></div>';


            $img = '<img src="' . base_url($value['image']) . '" alt="' . $value['name'] . '" class="img-circle" width="50" height="50" />';

            if ($value['price']) {
                $price = $value['price'];
            } else {
                $price = 0;
            }


            if ($value['qty']) {
                $qty = $value['qty'];
            } else {
                $qty = 0;
            }

            $uang = $price;
            $harga = number_format("$uang", 0, ",", ".");

            $angka = $price * $qty;
            $hargatotal = number_format("$angka", 0, ",", ".");
            $availability = ($value['availability'] == 1) ? '<span class="label label-success">Ada</span>' : '<span class="label label-warning">Tidak</span>';
            if ($value['tipe'] == 0) {
                $availability .= ' &nbsp;<span class="label label-primary">Umum</span>';
            } else if ($value['tipe'] == 1) {
                $availability .= ' &nbsp;<span class="label label-info">Baku</span>';
            } else if ($value['tipe'] == 2) {
                $availability .= ' &nbsp;<span class="label label-danger">Jadi</span>';
            };

            $qty_status = '';
            if ($value['qty'] <= 10) {
                $qty_status = '<span class="label label-warning">Low !</span>';
            } else if ($value['qty'] <= 0) {
                $qty_status = '<span class="label label-danger">Out !</span>';
            }



            $result['data'][$key] = array(
                $buttons,
                $value['sku'],
                $value['name'],
                'Rp. ' . $harga,

                $qty . '/' . $value['satuan'] . $qty_status,

                //Total Harga
                //'Rp. ' . $hargatotal,


                $availability,
            );
        } // /foreach

        echo json_encode($result);
    }

    public function create()
    {
        if (!in_array('createProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->form_validation->set_rules('availability', 'Availability', 'trim|required');
        $this->form_validation->set_rules('satuan', 'satuan', 'trim|required');


        $store_id = $this->session->userdata('store_id');
        if ($this->form_validation->run() == TRUE) {

            $cek = $this->model_products->ceknamaproduk($store_id, $this->input->post('product_name'));
            if ($cek > 0) {
                $this->session->set_flashdata('error', 'Nama Telah Ada!!');
                redirect('products/create', 'refresh');
            } else {
                // true case
                $upload_image = $this->upload_image();
                $user_id = $this->session->userdata('id');

                $i_kadaluarsa  = $this->input->post('kadaluarsa');
                $kds = date("Y-m-d", strtotime($i_kadaluarsa));
                if ($kds == '1970-01-01') {
                    $kadaluarsa = '';
                } else {
                    $kadaluarsa = $kds;
                }

                if ($this->input->post('ke')) {
                    $ke = implode(",", $this->input->post('ke'));
                } else {
                    $ke = '';
                }

                $data = array(
                    'gudang_id' => $store_id,
                    'name' => $this->input->post('product_name'),
                    'sku' => $this->input->post('sku'),
                    'tgl_input' => $this->input->post('tgl_input'),
                    'price' => $this->input->post('price'),
                    'qty' => $this->input->post('qty'),
                    'hpp' => $this->input->post('hpp'),
                    'ke' =>  $ke,
                    'satuan' => $this->input->post('satuan'),
                    'image' => $upload_image,
                    'description' => $this->input->post('description'),
                    'availability' => $this->input->post('availability'),
                    'kadaluarsa' => $kadaluarsa,
                    'user_id' => $user_id,
                    'tipe' => $this->input->post('tipe'),
                );

                $create = $this->model_products->create($data);
                if ($create == true) {
                    $this->session->set_flashdata('success', 'Produk Ditambahkan');
                    redirect('products/create', 'refresh');
                } else {
                    $this->session->set_flashdata('error', 'Terjadi Kesalahan Penambahan!!');
                    redirect('products/create', 'refresh');
                }
            }
        } else {
            // false case       

            $this->data['store'] = $this->model_stores->getStoresoutlet();
            $this->render_template('products/create', $this->data);
        }
    }

    public function upload_image()
    {
        // assets/images/product_image
        $config['upload_path'] = 'assets/images/product_image';
        $config['file_name'] =  uniqid();
        $config['allowed_types'] = 'gif|jpg|png';
        $config['max_size'] = '0';

        // $config['max_width']  = '1024';s
        // $config['max_height']  = '768';

        $this->load->library('upload', $config);
        if (!$this->upload->do_upload('product_image')) {
            $error = $this->upload->display_errors();
            return '';
        } else {
            $data = array('upload_data' => $this->upload->data());
            $type = explode('.', $_FILES['product_image']['name']);
            $type = $type[count($type) - 1];

            $path = $config['upload_path'] . '/' . $config['file_name'] . '.' . $type;
            return ($data == true) ? $path : false;
        }
    }

    public function update($product_id)
    {
        if (!in_array('updateProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        if (!$product_id) {
            redirect('dashboard', 'refresh');
        }

        $this->form_validation->set_rules('sku', 'SKU', 'trim|required');
        // $this->form_validation->set_rules('price', 'Price', 'trim|required');
        $this->form_validation->set_rules('qty', 'Qty', 'trim|required');
        $this->form_validation->set_rules('availability', 'Availability', 'trim|required');

        $store_id = $this->session->userdata('store_id');
        if ($this->form_validation->run() == TRUE) {
            $product_data = $this->model_products->getProductData($product_id);
            if ($product_data['name'] === $this->input->post('product_name')) {
                $cek = 0;
            } else {
                $cek = $this->model_products->ceknamaproduk($store_id, $this->input->post('product_name'));
            }

            if ($cek > 0) {
                $this->session->set_flashdata('error', 'Nama Telah Ada!!');
                redirect('products/create', 'refresh');
            } else {
                // true case
                $i_kadaluarsa  = $this->input->post('kadaluarsa');
                $kds = date("Y-m-d", strtotime($i_kadaluarsa));

                if ($kds == '1970-01-01') {
                    $kadaluarsa = '';
                } else {
                    $kadaluarsa = $kds;
                }

                if ($this->input->post('ke')) {
                    $ke = implode(",", $this->input->post('ke'));
                } else {
                    $ke = '';
                }

                if ($product_data['price'] == $this->input->post('price')) {
                    $harga = $product_data['price_old'];
                    $tgl = $product_data['price_tgl'];
                } else {
                    $tgl = date('Y-m-d');
                    $harga = $product_data['price'];
                }

                $data = array(
                    'gudang_id' => $store_id,
                    'name' => $this->input->post('product_name'),
                    'sku' => $this->input->post('sku'),
                    'satuan' => $this->input->post('satuan'),
                    'tgl_input' => $this->input->post('tgl_input'),
                    // 'price' => $this->input->post('price'),
                    'qty' => $this->input->post('qty'),
                    'ke' => $ke,
                    'hpp' => $this->input->post('hpp'),
                    'description' => $this->input->post('description'),
                    'availability' => $this->input->post('availability'),
                    'kadaluarsa' => $kadaluarsa,
                    'tipe' => $this->input->post('tipe'),
                    'price_old' => $harga,
                    'price_tgl' => $tgl
                );


                if ($_FILES['product_image']['size'] > 0) {
                    $upload_image = $this->upload_image();
                    $upload_image = array('image' => $upload_image);

                    $this->model_products->update($upload_image, $product_id);
                }

                $update = $this->model_products->update($data, $product_id);
                if ($update == true) {
                    $this->session->set_flashdata('success', 'Produk Diupdate');
                    redirect('products/', 'refresh');
                } else {
                    $this->session->set_flashdata('error', 'Terjadi Kesalahan Update!!');
                    redirect('products/update/' . $product_id, 'refresh');
                }
            }
        } else {
            $product_data = $this->model_products->getProductData($product_id);
            $this->data['store'] = $this->model_stores->getStoresoutlet();
            $this->data['product_data'] = $product_data;
            $this->render_template('products/edit', $this->data);
        }
    }

    public function remove()
    {
        if (!in_array('deleteProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $product_id = $this->input->post('product_id');

        $response = array();
        if ($product_id) {
            $delete = $this->model_products->remove($product_id);
            if ($delete == true) {
                $response['success'] = true;
                $response['messages'] = "Berhasil Terhapus";
            } else {
                $response['success'] = false;
                $response['messages'] = "Kesalahan dalam database saat menghapus informasi produk";
            }
        } else {
            $response['success'] = false;
            $response['messages'] = "Refersh kembali!!";
        }

        echo json_encode($response);
    }

    public function lmasuk()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('products/lmasuk', $this->data);
    }

    public function lkeluar()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }


        $user_id = $this->session->userdata('id');
        $user_data = $this->model_users->getUserData($user_id);
        $lihat = $user_data['group_id'];
        if ($lihat == 2) {
            $this->data['notif'] = $this->model_orders->upbaca(1);
        }
        if (isset($_GET['filter'])) {
            $dt = $this->model_stores->getStoresData($_GET['filter']);
            if ($dt) {
                $this->data['pilih'] = $dt['name'];
            } else {
                $this->data['pilih'] = 'Tidak ditemukan';
            }
        } else {
            $this->data['pilih'] = 'SEMUA';
        };
        $this->data['store'] = $this->model_stores->getStoresoutlet();
        $this->data['div'] = $this->session->userdata('divisi');
        $this->data['namastore'] = $this->session->userdata('store');

        $this->render_template('products/lkeluar', $this->data);
    }

    public function bmasuk()
    {
        if (!in_array('createProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->form_validation->set_rules('product_name', 'Product name', 'trim|required');
        $this->form_validation->set_rules('qty', 'Qty', 'trim|required');

        $store_id = $this->session->userdata('store_id');
        if ($this->form_validation->run() == TRUE) {
            $id = $this->input->post('product_name');
            $qtypost = $this->input->post('qty');
            $qtydata = $this->model_products->getProductData($id);
            $product_name = $qtydata['name'];
            $price = $qtydata['price'];
            $satuan = $qtydata['satuan'];
            $sku = $qtydata['sku'];
            $qty = $qtypost + $qtydata['qty'];


            $timezone = new DateTimeZone('Asia/Jakarta');
            $date = new DateTime();
            $date->setTimeZone($timezone);


            $tgl_bmasuk = $this->input->post('tgl_bmasuk');

            $data1 = array(
                // 'tgl_bmasuk' => $date->format('Y-m-d'),
                'gudang_id' => $store_id,
                'tgl_bmasuk' =>  $tgl_bmasuk,
                'name' => $product_name,
                'satuan' => $satuan,
                'sku' => $sku,
                'price' => $price,
                'qtymasuk' => $qtypost,
                'qtysblm' => $qtydata['qty'],
                'qtytotal' => $qty,
            );
            $data2 = array(
                'qty' => $qty,
            );

            $create = $this->model_products->createstock($data1);
            $update = $this->model_products->update($data2, $id);
            if ($update == true) {
                $this->session->set_flashdata('success', 'Stock bertambah');
                redirect('products/bmasuk', 'refresh');
            } else {
                $this->session->set_flashdata('error', 'Terjadi Kesalahan !!');
                redirect('products/bmasuk', 'refresh');
            }
        }

        $user_id = $this->session->userdata('id');
        $user_data = $this->model_users->getUserData($user_id);
        $lihat = $user_data['group_id'];
        if ($lihat == 2) {
            $this->data['notif'] = $this->model_orders->upbaca(1);
        }
        if (isset($_GET['filter'])) {
            $dt = $this->model_stores->getStoresData($_GET['filter']);
            if ($dt) {
                $this->data['pilih'] = $dt['name'];
            } else {
                $this->data['pilih'] = 'Tidak ditemukan';
            }
        } else {
            $this->data['pilih'] = 'SEMUA';
        };
        $this->data['store'] = $this->model_stores->getStoresoutlet();
        $this->data['div'] = $this->session->userdata('divisi');
        $this->data['namastore'] = $this->session->userdata('store');
        $this->data['products'] = $this->model_products->getActiveProductDataallgudang($store_id);


        $this->render_template('products/bmasuk', $this->data);
    }


    public function rmasuk()
    {
        if (!in_array('createProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->form_validation->set_rules('product_name', 'Product name', 'trim|required');
        $this->form_validation->set_rules('qty', 'Qty', 'trim|required');

        $store_id = $this->session->userdata('store_id');
        if ($this->form_validation->run() == TRUE) {
            $id = $this->input->post('product_name');
            $qtypost = $this->input->post('qty');
            $qtydata = $this->model_products->getProductData($id);
            $product_name = $qtydata['name'];
            $price = $qtydata['price'];
            $satuan = $qtydata['satuan'];
            $sku = $qtydata['sku'];
            $qty = $qtypost - $qtydata['qty'];


            $timezone = new DateTimeZone('Asia/Jakarta');
            $date = new DateTime();
            $date->setTimeZone($timezone);


            $tgl_bmasuk = $this->input->post('tgl_bmasuk');

            $data1 = array(
                // 'tgl_bmasuk' => $date->format('Y-m-d'),
                'gudang_id' => $store_id,
                'tgl_bmasuk' =>  $tgl_bmasuk,
                'name' => $product_name,
                'satuan' => $satuan,
                'sku' => $sku,
                'price' => $price,
                'qtymasuk' => $qtypost,
                'qtysblm' => $qtydata['qty'],
                'qtytotal' => $qty,
            );
            $data2 = array(
                'qty' => $qty,
            );

            $create = $this->model_products->createstockrusak($data1);
            $update = $this->model_products->update($data2, $id);
            if ($update == true) {
                $this->session->set_flashdata('success', 'Stock dikurangi');
                redirect('products/rmasuk', 'refresh');
            } else {
                $this->session->set_flashdata('error', 'Terjadi Kesalahan !!');
                redirect('products/rmasuk', 'refresh');
            }
        }

        $user_id = $this->session->userdata('id');
        $user_data = $this->model_users->getUserData($user_id);
        $lihat = $user_data['group_id'];
        if ($lihat == 2) {
            $this->data['notif'] = $this->model_orders->upbaca(1);
        }
        if (isset($_GET['filter'])) {
            $dt = $this->model_stores->getStoresData($_GET['filter']);
            if ($dt) {
                $this->data['pilih'] = $dt['name'];
            } else {
                $this->data['pilih'] = 'Tidak ditemukan';
            }
        } else {
            $this->data['pilih'] = 'SEMUA';
        };
        $this->data['store'] = $this->model_stores->getStoresoutlet();
        $this->data['div'] = $this->session->userdata('divisi');
        $this->data['namastore'] = $this->session->userdata('store');
        $this->data['products'] = $this->model_products->getActiveProductDataallgudang($store_id);


        $this->render_template('products/rmasuk', $this->data);
    }

    public function laporanstockmasuk()
    {
        $result = array('data' => array());

        $store_id = $this->session->userdata('store_id');
        $data = $this->model_products->getProductstockDatagudang($store_id);

        foreach ($data as $key => $value) {


            $uang = $value['price'];
            $harga = number_format("$uang", 0, ",", ".");

            $angka = $value['price'] * $value['qtymasuk'];
            $hargatotal = number_format("$angka", 0, ",", ".");


            $result['data'][$key] = array(
                $value['tgl_bmasuk'],
                $value['sku'],
                $value['name'],
                $value['qtysblm'] . '/' . $value['satuan'],
                $value['qtymasuk'] . '/' . $value['satuan'],
                $value['qtytotal'],
                'Rp. ' . $harga,
                //Total Harga
                'Rp. ' . $hargatotal,
            );
        } // /foreach

        echo json_encode($result);
    }
    public function laporanstockrusak()
    {
        $result = array('data' => array());

        $store_id = $this->session->userdata('store_id');
        $data = $this->model_products->getProductstockDataRusakGudang($store_id);

        foreach ($data as $key => $value) {


            $uang = $value['price'];
            $harga = number_format("$uang", 0, ",", ".");

            $angka = $value['price'] * $value['qtymasuk'];
            $hargatotal = number_format("$angka", 0, ",", ".");


            $result['data'][$key] = array(
                $value['tgl_bmasuk'],
                $value['sku'],
                $value['name'],
                $value['qtysblm'] . '/' . $value['satuan'],
                $value['qtymasuk'] . '/' . $value['satuan'],
                $value['qtytotal'],
                'Rp. ' . $harga,
                //Total Harga
                'Rp. ' . $hargatotal,
            );
        } // /foreach

        echo json_encode($result);
    }

    public function laporanstock()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }
        $this->load->view('products/laporan/laporanstock', $this->data);
    }

    public function cetaklaporanbmasuk()
    {


        $settgl_awal  = $this->input->post('tgl_awal');
        $tgl_awal = date("Y-m-d", strtotime($settgl_awal));

        $settgl_akhir   = $this->input->post('tgl_akhir');
        $tgl_akhir = date("Y-m-d", strtotime($settgl_akhir));

        $data = array();
        $data['title'] = "Laporan Masuk Dari Tanggal " . $tgl_awal . ' Sampai ' . $tgl_akhir;
        $data['hasil'] = $this->model_products->cetakpertanggalmasukstock($tgl_awal, $tgl_akhir);
        $this->load->view('products/laporan/cetaklaporanbmasuk', $data);
    }

    public function cetaklaporanmasuk()
    {


        $settgl_awal  = $this->input->post('tgl_awal');
        $tgl_awal = date("Y-m-d", strtotime($settgl_awal));

        $settgl_akhir   = $this->input->post('tgl_akhir');
        $tgl_akhir = date("Y-m-d", strtotime($settgl_akhir));

        $data = array();
        $data['title'] = "Laporan Masuk Dari Tanggal " . $tgl_awal . ' Sampai ' . $tgl_akhir;
        $data['hasil'] = $this->model_products->cetakpertanggal($tgl_awal, $tgl_akhir);
        $this->load->view('products/laporan/cetaklaporanmasuk', $data);
    }

    public function laporanmasuk()
    {
        $result = array('data' => array());

        $store_id = $this->session->userdata('store_id');
        $data = $this->model_products->getProductDataGudang($store_id);

        foreach ($data as $key => $value) {


            $uang = $value['price'];
            $harga = number_format("$uang", 0, ",", ".");


            if ($value['qty']) {
                $qty = $value['qty'];
            } else {
                $qty = 0;
            }

            $angka = $value['price'] * $qty;
            $hargatotal = number_format("$angka", 0, ",", ".");

            $qty_status = '';
            if ($value['qty'] <= 10) {
                $qty_status = '<span class="label label-warning">Low !</span>';
            } else if ($value['qty'] <= 0) {
                $qty_status = '<span class="label label-danger">Out !</span>';
            }




            $result['data'][$key] = array(
                $value['tgl_input'],
                $value['sku'],
                $value['name'],
                'Rp. ' . $harga,

                $qty . '/' . $value['satuan'] . $qty_status,

                //Total Harga
                'Rp. ' . $hargatotal,
            );
        } // /foreach

        echo json_encode($result);
    }

    public function laporankeluar()
    {
        $result = array('data' => array());

        $filter = $this->input->post('filter');
        $var = $this->input->post('tgl');
        $div = $this->session->userdata('divisi');
        $id_user = $this->session->userdata('id');
        $store_id = $this->session->userdata('store_id');

        $str  = $this->model_stores->getStoresData($store_id);


        if ($var) {
            if (is_numeric($filter) or $filter == '') {
                $tgl = str_replace('/', '-', $var);
                $hasil = explode(" - ", $tgl);
                $dari = strtotime("-1 day", strtotime($hasil[0]));
                $sampai = strtotime("+1 day", strtotime($hasil[1]));

                $cek = $this->model_stores->getStoresData($store_id);
                if ($filter) {
                    if ($cek['tipe'] == 2) {
                        $data = $this->model_orders->getOrdersDatabyoutletgudang($filter, $dari, $sampai, $store_id);
                    } else {
                        $data = $this->model_orders->getOrdersDatabystoreid($filter, $dari, $sampai);
                    }
                } else {
                    if ($cek['tipe'] == 2) {
                        $data = $this->model_orders->getOrdersDatabyallgudang($dari, $sampai, $store_id);
                    } else {
                        $data = $this->model_orders->getOrdersDatabyall($dari, $sampai);
                    }
                }

                foreach ($data as $key => $value) {

                    $count_total_item = $this->model_orders->countOrderItem($value['id']);
                    $date_time = date('d-m-Y', $value['date_time']);

                    // button

                    $status = $value['status_up'];

                    if ($status == 0) {
                        $buttons = ' <div class="btn-group dropleft">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"> <span class="caret"></span></button>
                                <ul class="dropdown-menu">';

                        if ($str['tipe'] == 2) {
                            $buttons .= '<li><a style="cursor:pointer;" onclick="upload(' . $value['id'] . ')"><i class="fa fa-upload"></i> Upload</a></li>';
                        }

                        $buttons .= '<li><a  href="' . base_url('products/lihat/' . $value['id']) . '" ><i class="fa fa-eye"></i> Lihat</a></li>';
                        $buttons .= '</ul></div>';
                    } elseif ($status == 1) {
                        $buttons = '<span class="label label-success">Terupload</span>';
                    }

                    if ($value['paid_status'] == 1) {
                        $paid_status = '<span class="label label-success">Paid</span>';
                    } else {
                        $paid_status = '<span class="label label-warning">Not Paid</span>';
                    }

                    $uang = $value['net_amount'];
                    $harga = number_format("$uang", 0, ",", ".");

                    $result['data'][$key] = array(
                        $buttons,
                        $value['bill_no'],
                        $value['customer_name'],
                        $value['customer_address'],
                        $date_time,
                        $count_total_item,
                        'Rp. ' . $harga,
                        $paid_status,
                    );
                } // /foreach
            } else {
                $result['data'] = array();
            }
        } else {
            $result['data'] = array();
        }
        echo json_encode($result);
    }

    public function cetaklaporankeluar()
    {


        $settgl_awal  = $this->input->post('tgl_awal');
        $tgl_awal = date("Y-m-d", strtotime($settgl_awal));

        $settgl_akhir   = $this->input->post('tgl_akhir');
        $tgl_akhir = date("Y-m-d", strtotime($settgl_akhir));

        $data = array();
        $data['title'] = "Laporan Keluar Dari Tanggal " . $tgl_awal . ' Sampai ' . $tgl_akhir;
        $data['tgl_awal'] = $tgl_awal;;
        $data['tgl_akhir'] = $tgl_akhir;

        $data['hasil'] = $this->model_orders->getOrdersstore(1, $tgl_awal, $tgl_akhir);
        $this->load->view('products/laporan/cetaklaporankeluar', $data);
    }

    public function status_up()
    {

        $id = $this->input->post('id');
        if (!$id) {
            echo 1;
        } else {

            //data order_item
            $dataorder = $this->model_orders->getOrdersData($id);
            $row = $this->model_orders->status_up($id);

            $qtydeliv = array();
            $qtyarv = array();
            foreach ($row as $key => $value) {
                $qtydeliv[] = $value['qtydeliv'];
                $qtyarv[] = $value['qtyarv'];
            }
            $cekdeliv = array_sum($qtydeliv);
            $cekarv = array_sum($qtyarv);

            if ($qtydeliv == $qtyarv) {

                if ($cekdeliv && $cekarv) {

                    foreach ($row as $key => $value) {

                        $idproduct = $value['product_id'];
                        $qtydeliv = $value['qtydeliv'];
                        $idorderitem = $value['id'];
                        $status_up = $value['status_up'];


                        if ($status_up == 0) {
                            //data produk
                            $product = $this->model_products->getProductData($idproduct);
                            $qtyp = $product['qty'];
                            $qty = $qtyp - $qtydeliv;
                            $data = array('qty' => $qty,);


                            $status = 1;
                            $data1 = array('status_up' => $status,);

                            //update ke produk
                            $update = $this->model_products->update($data, $idproduct);
                            //update ke order item
                            $update = $this->model_orders->updateorderitem($data1, $idorderitem);
                            //update ke order
                            $update = $this->model_orders->updateorder($data1, $id);
                        } else {
                            echo 9;
                        }
                    } //forech

                    if ($update == true) {

                        //masuk ke laporan
                        $date = date('Y-m-d', $dataorder['date_time']);

                        $laporan = $this->model_products->getlaporantgl($date);
                        if ($laporan > 0) {

                            $hapus = $this->model_products->removelaporan($date);
                            if ($hapus == true) {
                                $insert = $this->model_products->createlaporan();
                                if ($insert == true) {
                                    echo 6;
                                }
                            }
                        } else {
                            $uptgl = $this->model_products->updatetgllaporan($date);
                            if ($uptgl == true) {
                                $insert = $this->model_products->createlaporan();
                                if ($insert == true) {
                                    echo 6;
                                }
                            }
                        }
                    } else {
                        echo 4;
                    }
                } else {
                    echo 3;
                }
            } else {
                echo 2;
            }
        }
    }

    public function orderdata()
    {
        $result = array('data' => array());

        $data = $this->model_products->getProductData();

        foreach ($data as $key => $value) {


            // button
            $buttons = '';
            if (in_array('updateProduct', $this->permission)) {
                $buttons .= '<a href="' . base_url('products/update/' . $value['id']) . '" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
            }

            if (in_array('deleteProduct', $this->permission)) {
                $buttons .= ' <button type="button" class="btn btn-default" onclick="removeFunc(' . $value['id'] . ')" data-toggle="modal" data-target="#removeModal"><i class="fa fa-trash"></i></button>';
            }


            $img = '<img src="' . base_url($value['image']) . '" alt="' . $value['name'] . '" class="img-circle" width="50" height="50" />';


            if ($value['price']) {
                $price = $value['price'];
            } else {
                $price = 0;
            }

            $uang = $price;
            $harga = number_format("$uang", 0, ",", ".");


            if ($value['qty']) {
                $qty = $value['qty'];
            } else if ($value['qty'] <= 0) {
                $qty = 0;
            }

            $angka = $price * $qty;
            $hargatotal = number_format("$angka", 0, ",", ".");
            $availability = ($value['availability'] == 1) ? '<span class="label label-success">Tersedia</span>' : '<span class="label label-warning">Belum Tersedia</span>';

            $qty_status = '';
            if ($value['qty'] <= 10) {
                $qty_status = '<span class="label label-warning">Low !</span>';
            } else if ($value['qty'] <= 0) {
                $qty_status = '<span class="label label-danger">Out !</span>';
            }



            $result['data'][$key] = array(
                $value['sku'],
                $value['name'],
                'Rp. ' . $harga,

                $qty . '/' . $value['satuan'] . $qty_status,
                $availability,
            );
        } // /foreach

        echo json_encode($result);
    }

    public function kadaluarsa()
    {
        $result = array('data' => array());

        $store_id = $this->session->userdata('store_id');
        $data = $this->model_products->cekkadaluarsa($store_id);

        foreach ($data as $key => $value) {



            $result['data'][$key] = array(
                $value['sku'],
                $value['name'],
                $value['kadaluarsa'],
            );
        } // /foreach

        echo json_encode($result);
    }

    public function qtylow()
    {
        $result = array('data' => array());

        $store_id = $this->session->userdata('store_id');
        $data = $this->model_products->qtylow($store_id);

        foreach ($data as $key => $value) {



            $result['data'][$key] = array(
                $value['sku'],
                $value['name'],
                $value['qty'],
            );
        } // /foreach

        echo json_encode($result);
    }

    public function laporanstockproduk()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $this->render_template('products/laporan/laporanstockproduk', $this->data);
    }

    public function fetchlaporanstock()
    {
        $result = array('data' => array());

        $data = $this->model_products->getlaporan();

        foreach ($data as $key => $value) {

            if ($value['harga']) {
                $price = $value['harga'];
            } else {
                $price = 0;
            }


            if ($value['qty']) {
                $qty = $value['qty'];
            } else {
                $qty = 0;
            }

            $uang = $price;
            $harga = number_format("$uang", 0, ",", ".");

            $angka = $price * $qty;
            $hargatotal = number_format("$angka", 0, ",", ".");

            $result['data'][$key] = array(
                $value['tgl'],
                $value['nama'],
                $value['sku'],
                'Rp. ' . $harga,

                $qty . '/' . $value['satuan'],

                //Total Harga
                'Rp. ' . $hargatotal,

            );
        } // /foreach

        echo json_encode($result);
    }

    public function laporan()
    {
        if (!in_array('viewProduct', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $settgl_awal  = $this->input->post('tgl_awal');
        $settgl_akhir   = $this->input->post('tgl_akhir');
        $outlet  = $this->input->post('outlet');

        if ($settgl_awal & $settgl_akhir) {
            $tgl_awal = date("Y-m-d", strtotime($settgl_awal));
            $tgl_akhir = date("Y-m-d", strtotime($settgl_akhir));

            $data['title'] = "Laporan Dari Tanggal " . $tgl_awal . ' Sampai ' . $tgl_akhir;
            $data['tgl_awal'] = $tgl_awal;
            $data['tgl_akhir'] = $tgl_akhir;
            $data['hasil'] = $this->model_orders->getOrdersstore($outlet, $tgl_awal, $tgl_akhir);
            $data['hasilm'] = $this->model_products->cetakpertanggalmasukstock($tgl_awal, $tgl_akhir);
            $this->load->view('products/laporan/print', $data);
        } else {
            $this->data['store'] = $this->model_stores->getStoresoutlet();
            $this->render_template('products/laporan', $this->data);
        }
    }


    public function excel()
    {
        $div = $this->session->userdata('divisi');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $spreadsheet->getProperties()
            ->setCreator("Fembi Nur Ilham")
            ->setLastModifiedBy("Fembi Nur Ilham")
            ->setTitle("Stock Logistik")
            ->setSubject("Hasil Export Dari PRS System")
            ->setDescription("Semoga Terbantu Dengan Adanya Ini")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Stock Logistik");


        $filename = "Stock Produk Logistik " . date('d-m-Y') . ".xlsx";

        $sheet->setCellValue('A1', 'Stock Produk Logistik');
        $sheet->setCellValue('A2', 'Tanggal ' . date('d-m-Y'));
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);

        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'Nama Produk');
        $sheet->setCellValue('C4', 'Harga');
        $sheet->setCellValue('D4', 'Satuan');
        $sheet->setCellValue('E4', 'Qty Tersedia');
        $sheet->setCellValue('F4', 'Total Harga');

        $data = $this->model_products->getProductData();
        $baris = 5;
        $no = 1;
        $count = 4;
        $hrgjml = 0;
        if ($data) {
            foreach ($data as $key => $value) {

                if ($value['price'] && $value['qty']) {
                    $angka = $value['price'] * $value['qty'];
                } else if ($value['qty']) {
                    $angka = 0 * $value['qty'];
                } else {
                    $angka = 0 * 0;
                }
                $sheet->setCellValue('A' . $baris, $no++);
                $sheet->setCellValue('B' . $baris, $value['name']);
                $sheet->setCellValue('C' . $baris, $value['price']);
                $sheet->setCellValue('D' . $baris,  $value['satuan']);
                $sheet->setCellValue('E' . $baris, $value['qty']);
                $sheet->setCellValue('F' . $baris, $angka);

                $baris++;
                $count++;
                $hrgjml += $angka;
            }
            $jmlh = $count + 1;
            $spreadsheet->getActiveSheet()->mergeCells('A' . $jmlh . ':E' . $jmlh);
            $sheet->setCellValue('A' . $jmlh, 'Jumlah');
            $sheet->setCellValue('F' . $jmlh, $hrgjml);

            $writer = new Xlsx($spreadsheet);
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Content-Type: application/vnd.ms-excel');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
        } else {
            $this->session->set_flashdata('error', 'Data Tidak Ditemukan');
            redirect('orders/', 'refresh');
        }
    }


    public function printmasuk()
    {
        $product  = $this->input->post('product');
        $jml  = $this->input->post('jml');
        $tgl  = $this->input->post('tgl');
        if ($tgl && $jml && $product) {
            $html = '<!-- Main content -->
			<!DOCTYPE html>
			<html>
			<head>
			  <meta charset="utf-8">
			  <meta http-equiv="X-UA-Compatible" content="IE=edge">
			  <title>Invoice Order</title>
			  <!-- Tell the browser to be responsive to screen width -->
			  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
			  <!-- Bootstrap 3.3.7 -->
			  <link rel="stylesheet" href="' . base_url('assets/bower_components/bootstrap/dist/css/bootstrap.min.css') . '">
			  <!-- Font Awesome -->
			  <link rel="stylesheet" href="' . base_url('assets/bower_components/font-awesome/css/font-awesome.min.css') . '">
			  <link rel="stylesheet" href="' . base_url('assets/dist/css/AdminLTE.min.css') . '">
			</head>
			<body onload="window.print();">
			<style>html, body {height:unset;}</style>
			<div class="wrapper" style="width: 55mm;height:unset;">
			  <section class="invoice">
			    <!-- /.row -->

			    <!-- Table row -->
			    <div class="row">
			      <div class="col-xs-12 table-responsive">
			        <table class="table"  style="font-size: 20px; width:100%;">
			        
			          <tbody>';
            for ($x = 0; $x <= $jml; $x++) {

                $html .= '<tr style="border-bottom: solid;">
				            <td colspan="3" style="text-align: center;padding: 5px;line-height: normal;">' . $product . '<br>' . $tgl . '</td>
			          	</tr>';
            }

            $html .= '</tbody>
			        </table>
			      </div>
			      <!-- /.col -->
			    </div>
			    <!-- /.row -->

			  </section>
			  <!-- /.content -->
			</div>
			</body>
			</html>';

            echo $html;
        } else {
            $this->session->set_flashdata('error', 'Masukkan Data Dengan Benar !');
            redirect('products/', 'refresh');
        }
    }



    public function lihat($id)
    {
        if (!$id) {
            redirect('dashboard', 'refresh');
        }

        $this->data['page_title'] = 'Update Order';
        $this->data['orderdata'] = $this->model_orders->getOrdersData($id);

        $div = $this->session->userdata('divisi');
        $store_id = $this->session->userdata('store_id');

        $this->form_validation->set_rules('product[]', 'Product name', 'trim|required');
        if ($this->form_validation->run() == TRUE) {

            $hitung = $this->input->post('product[]');
            $clear_array = array_count_values($hitung);
            $au = array_keys($clear_array);
            $ay = array_values($hitung);

            if ($au == $ay) {
                $update = $this->model_orders->update($id);

                if ($update == true) {
                    $this->session->set_flashdata('success', 'Data Berhasil Masuk');
                    redirect('orders/update/' . $id, 'refresh');
                } else {
                    $this->session->set_flashdata('errors', 'Error occurred!!');
                    redirect('orders/update/' . $id, 'refresh');
                }
            } else {
                $this->session->set_flashdata('error', 'Maaf.. ! Produk Pesanan Yang diedit Ada Yang Ganda');
                redirect('orders/update/' . $id, 'refresh');
            }
        } else {
            // false case
            $company = $this->model_company->getCompanyData(1);
            $this->data['company_data'] = $company;
            $this->data['is_vat_enabled'] = ($company['vat_charge_value'] > 0) ? true : false;
            $this->data['is_service_enabled'] = ($company['service_charge_value'] > 0) ? true : false;

            $result = array();
            $orders_data = $this->model_orders->getOrdersData($id);

            if (isset($orders_data)) {
                $result['order'] = $orders_data;
                $orders_item = $this->model_orders->getOrdersItemData($orders_data['id']);

                foreach ($orders_item as $k => $v) {
                    $result['order_item'][] = $v;
                }

                $this->data['order_data'] = $result;

                if ($div == 0) {
                    $this->data['products'] = $this->model_products->getProductData();
                } else {
                    $this->data['products'] = $this->model_products->getActiveProductData($store_id);
                }
            } else {
                $this->session->set_flashdata('error', 'Maaf.. ! Anda Tidak Punya Hak Akses');
                redirect('orders/', 'refresh');
            }

            $this->render_template('orders/edit', $this->data);
        }
    }




    public function masukbelanja()
    {
        $result = array('data' => array());

        $var = $this->input->post('tgl');

        $store_id = $this->session->userdata('store_id');

        if ($var) {
            $tgl = str_replace('/', '-', $var);
            $hasil = explode(" - ", $tgl);
            $dari = date('Y-m-d', strtotime("-1 day", strtotime($hasil[0])));
            $sampai =  date('Y-m-d', strtotime("+1 day", strtotime($hasil[1])));

            $data = $this->model_belanja->getbelanjaterimabyall($dari, $sampai, $store_id);

            foreach ($data as $key => $value) {
                // button

                $status = $value['upload'];

                if ($status == 0) {
                    $buttons = ' <div class="btn-group dropleft">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"> <span class="caret"></span></button>
                                <ul class="dropdown-menu">';
                    $buttons .= '<li><a style="cursor:pointer;" onclick="upload(' . $value['id'] . ')"><i class="fa fa-upload"></i> Upload</a></li>';
                    $buttons .= '<li><a  href="' . base_url('belanja/edit/' . $value['id']) . '" ><i class="fa fa-eye"></i> Lihat</a></li>';
                    $buttons .= '</ul></div>';
                } elseif ($status == 1) {
                    $buttons = '<span class="label label-success">Terupload</span>';
                }

                $result['data'][$key] = array(
                    $buttons,
                    $value['bill_no'],
                    $value['tgl'],
                    $value['total']
                );
            } // /foreach
        } else {
            $result['data'] = array();
        }
        echo json_encode($result);
    }

    public function uploadbarangmasuk()
    {

        $id = $this->input->post('id');
        $store_id = $this->session->userdata('store_id');
        // $id = 15;

        $belanja = $this->model_belanja->getbelanjaData($id);
        $itembelanja = $this->model_belanja->getbelanjaid($id);

        $status = '';
        foreach ($itembelanja as $val) {
            $produk = $this->model_products->getProductData($val['product_id']);

            $name = $produk['name'];
            $sku = $produk['sku'];
            $price = $produk['price'];
            $satuan = $produk['satuan'];
            $qtysblm = $produk['qty'];
            $qtymasuk = $val['qty'];
            $qtytotal = $qtysblm + $qtymasuk;
            $tglbmasuk = $belanja['tgl'];


            $data1 = array(
                'gudang_id' => $store_id,
                'tgl_bmasuk' => $tglbmasuk,
                'name' => $name,
                'satuan' => $satuan,
                'sku' => $sku,
                'price' => $price,
                'qtymasuk' => $qtymasuk,
                'qtysblm' => $qtysblm,
                'qtytotal' => $qtytotal,
            );

            $data2 = array(
                'qty' => $qtytotal,
            );

            $create = $this->model_products->createstock($data1);
            if ($create == true) {
                $update = $this->model_products->update($data2, $val['product_id']);
                if ($update == true) {
                    $this->model_belanja->uploadsuksesitem($val['id']);
                    $status .= '';
                } else {
                    $status .= $name . ' ';
                }
            }
        }

        if ($status) {
            echo $status;
        } else {
            $this->model_belanja->uploadsukses($id);
        }
    }


    public function uploadbarangjadi()
    {

        $id = $this->input->post('id');
        $store_id = $this->session->userdata('store_id');

        $brng = $this->model_dapro->getdaprojadiid($id);

        $status = '';
        if ($brng) {
            $produk = $this->model_products->getProductData($brng['idproduct']);

            $name = $produk['name'];
            $sku = $produk['sku'];
            $price = $produk['price'];
            $satuan = $produk['satuan'];
            $qtysblm = $produk['qty'];
            $qtymasuk = $brng['qty'];
            $qtytotal = $qtysblm + $qtymasuk;
            $tglbmasuk = $brng['tgl'];


            $data1 = array(
                'gudang_id' => $store_id,
                'tgl_bmasuk' => $tglbmasuk,
                'name' => $name,
                'satuan' => $satuan,
                'sku' => $sku,
                'price' => $price,
                'qtymasuk' => $qtymasuk,
                'qtysblm' => $qtysblm,
                'qtytotal' => $qtytotal,
            );

            $data2 = array(
                'qty' => $qtytotal,
            );

            $create = $this->model_products->createstock($data1);
            if ($create == true) {
                $update = $this->model_products->update($data2, $brng['idproduct']);
                if ($update == true) {
                    $this->model_dapro->uploadsuksesitem($brng['id']);
                    $status .= '';
                } else {
                    $status .= $name . ' ';
                }
            }
        }

        if ($status) {
            echo $status;
        }
    }

    public function masukbarangjadi()
    {
        $result = array('data' => array());

        $var = $this->input->post('tgl');


        if ($var) {
            $tgl = str_replace('/', '-', $var);
            $hasil = explode(" - ", $tgl);
            $dari = date('Y-m-d', strtotime("-1 day", strtotime($hasil[0])));
            $sampai =  date('Y-m-d', strtotime("+1 day", strtotime($hasil[1])));

            $data = $this->model_belanja->getbarangjadibyall($dari, $sampai);

            foreach ($data as $key => $value) {
                // button

                $status = $value['up'];

                if ($status == 0) {
                    $buttons = ' <div class="btn-group dropleft">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"> <span class="caret"></span></button>
                                <ul class="dropdown-menu">';
                    $buttons .= '<li><a style="cursor:pointer;" onclick="upload1(' . $value['id'] . ')"><i class="fa fa-upload"></i> Upload</a></li>';
                    $buttons .= '</ul></div>';
                } elseif ($status == 1) {
                    $buttons = '<span class="label label-success">Terupload</span>';
                }

                $total = $value['qty'] * $value['harga'];
                $result['data'][$key] = array(
                    $buttons,
                    $value['tgl'],
                    $value['nama'],
                    $value['qty'],
                    $value['harga'],
                    $total
                );
            } // /foreach
        } else {
            $result['data'] = array();
        }
        echo json_encode($result);
    }




    public function kasir()
    {
        if (!in_array('createOrder', $this->permission)) {
            redirect('dashboard', 'refresh');
        }

        $div = $this->session->userdata('divisi');
        $store_id = $this->session->userdata('store_id');
        $div = $this->session->userdata('divisi');
        $store = $this->session->userdata('store');
        $user_id = $this->session->userdata('id');


        $this->form_validation->set_rules('product[]', 'Product name', 'trim|required');
        if ($this->form_validation->run() == TRUE) {
            $hitung = $this->input->post('product');
            $clear_array = array_count_values($hitung);
            $au = array_keys($clear_array);
            $ay = array_values($hitung);

            if ($au == $ay) {
                $order_id = $this->model_orders->create();
                if ($order_id) {
                    $akhir = $this->model_orders->getOrdersakhir($store_id);
                    echo $akhir['id'];
                    if ($akhir['id']) {
                        $count_product = count($this->input->post('product'));
                        for ($x = 0; $x < $count_product; $x++) {
                            $idpr = $this->input->post('product')[$x];
                            $qtyinp = $this->input->post('qty')[$x];


                            $datanya = $this->model_products->getProductData($idpr);
                            if (isset($datanya['qty'])) {

                                $qty = $datanya['qty'] - $qtyinp;
                                $items = array(
                                    'qty' => $qty,
                                );

                                $this->db->where('id', $datanya['id']);
                                $this->db->update('products', $items);
                            }
                        }
                    }
                } else {
                    echo 'ere';
                }
            } else {
                echo 'dup';
            }
        } else {
            // false case
            $company = $this->model_company->getCompanyData(1);
            $this->data['company_data'] = $company;
            $this->data['is_vat_enabled'] = ($company['vat_charge_value'] > 0) ? true : false;
            $this->data['is_service_enabled'] = ($company['service_charge_value'] > 0) ? true : false;

            if ($div == 0) {
                $this->data['products'] = $this->model_products->getProductDataGudang($store_id);
            } else {
                $this->data['products'] = $this->model_products->getActiveProductData($store_id);
            }
            $this->data['page_title'] = 'Point Of Sales (POS)';

            $this->data['outlet'] = $store;
            $this->data['div'] = $div;
            $this->data['store_id'] = $store_id;
            $this->data['user'] = $this->model_users->getUserData($user_id);
            $this->data['store'] = $this->model_stores->getStoresoutlet();

            $this->render_template('products/kasir', $this->data);
        }
    }

    public function kasir_print()
    {

        $id = $this->input->post('id');

        if (!$id) {
            redirect('dashboard', 'refresh');
        }

        if ($id) {
            $order_data = $this->model_orders->getOrdersData($id);
            $orders_items = $this->model_orders->getOrdersItemData($id);
            $company_info = $this->model_company->getCompanyData(1);

            $order_date = date('d/m/Y', $order_data['date_time']);
            $paid_status = ($order_data['paid_status'] == 1) ? "Paid" : "Unpaid";

            $html = '<!-- Main content -->
			<!DOCTYPE html>
			<html>
			<head>
			  <meta charset="utf-8">
			  <meta http-equiv="X-UA-Compatible" content="IE=edge">
			  <title>Invoice Order</title>
			  <!-- Tell the browser to be responsive to screen width -->
			  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
			  <!-- Bootstrap 3.3.7 -->
			  <link rel="stylesheet" href="' . base_url('assets/bower_components/bootstrap/dist/css/bootstrap.min.css') . '">
			  <!-- Font Awesome -->
			  <link rel="stylesheet" href="' . base_url('assets/bower_components/font-awesome/css/font-awesome.min.css') . '">
			  <link rel="stylesheet" href="' . base_url('assets/dist/css/AdminLTE.min.css') . '">
			</head>
			<body onload="window.print();">
			<style>html, body {height:unset;}</style>
			<div class="wrapper" style="width: 55mm;height:unset;">
			  <section class="invoice">
			    <!-- title row -->
			    <div class="row">
			      <div class="col-xs-12">
			        <h2 class="page-header" style="font-size: 18px; text-align:center;margin-top: -7px;">
					<img style="filter: grayscale(100%);" width="100px" src="' . base_url() . '/assets/images/logo/prslogin.png"><br> 
			          ' . $company_info['company_name'] . '
			          <small class="pull-right"><br>' . $order_date . '</small>
			        </h2>
			      </div>
			      <!-- /.col -->
			    </div>
			    <!-- info row -->
			    <div class="row invoice-info">
			      
			      <div class="col-sm-4 invoice-col" style="font-size: 12px; width:100%;">
			        
			        <b>Bill ID:</b> ' . $order_data['bill_no'] . '<br>
			        <b>Penerima:</b> ' . $order_data['customer_name'] . '<br>
			        <b>Dari :</b> ' . $order_data['customer_address'] . ' <br />
			        <b>No Hp:</b> ' . $order_data['customer_phone'] . '
			      </div>
			      <!-- /.col -->
			    </div>
			    <!-- /.row -->

			    <!-- Table row -->
			    <div class="row">
			      <div class="col-xs-12 table-responsive">
			        <table class="table"  style="font-size: 12px; width:100%;">
			        
			          <tbody>';
            $no = 1;
            $jmltotal = 0;
            foreach ($orders_items as $k => $v) {

                $product_data = $this->model_products->getProductData($v['product_id']);


                if ($v['rate']) {
                    $hrg = $v['rate'];
                } else {
                    $hrg = 0;
                }

                if ($v['qty']) {
                    $qtydb = $v['qty'];
                } else {
                    $qtydb = 0;
                }

                if ($v['qtydeliv']) {
                    $jumlah = $v['qtydeliv'] * $hrg;
                } else {
                    $jumlah = $qtydb * $hrg;
                }

                if ($v['qtydeliv']) {
                    $qty = $v['qtydeliv'];
                } else {
                    $qty = $qtydb;
                }

                if ($v['qtydeliv'] == '0') {
                    $qty = $v['qtydeliv'];
                    $jumlah = $v['qtydeliv'] * $hrg;
                }

                if (isset($product_data['name'])) {
                    $nama = $product_data['name'];
                } else {
                    $nama = $v['nama_produk'];
                }

                $jmltotal += $jumlah;

                $html .= '<tr style="border-bottom: 1px dashed ;">
				            <td colspan="3" style="text-align: center;padding: 5px;line-height: normal;">' . $nama . '<br>' . $qty . ' X ' . $hrg . '/' . $v['satuan'] . ' = Rp.' . number_format($jumlah, 0, ',', '.') . '</td>
			          	</tr>';
            }

            $html .= '</tbody>
			        </table>
			      </div>
			      <!-- /.col -->
			    </div>
			    <!-- /.row -->

			    <div class="row">
			      
			      <div class="col-xs-6 pull pull-right" style="font-size: 12px; width:100%; margin-top: -20px;">

			        <div class="table-responsive">
			          <table class="table" >
			           ';

            if ($order_data['service_charge'] > 0) {
                $html .= '<tr>
				              <th>Service Charge (' . $order_data['service_charge_rate'] . '%)</th>
				              <td>Rp. ' . $order_data['service_charge'] . '</td>
				            </tr>';
            }

            if ($order_data['vat_charge'] > 0) {
                $html .= '<tr>
				              <th>Vat Charge (' . $order_data['vat_charge_rate'] . '%)</th>
				              <td>' . $order_data['vat_charge'] . '</td>
				            </tr>';
            }


            $html .= ' 
			            <tr style="border-bottom: 1px dashed ;">
			              <th>Total:</th>
			              <td>Rp. ' . number_format($jmltotal, 0, ',', '.') . '</td>
			            </tr>

			            <tr style="border-bottom: 1px dashed ;">
			              <th>Tunai:</th>
			              <td>Rp. ' . number_format($order_data['tunai'], 0, ',', '.') . '</td>
			            </tr>
			           
			            <tr style="border-bottom: 1px dashed ;">
			              <th>Kembalian:</th>
			              <td>Rp. ' . number_format($order_data['tunai'] - $jmltotal, 0, ',', '.') . '</td>
			            </tr>

			          </table>
                      <center>*Terima Kasih*<br>------------------------</center>
			      <!-- /.col -->
			    </div>
			    <!-- /.row -->
			  </section>
			  <!-- /.content -->
			</div>
			</body>
			</html>';

            echo $html;
        }
    }

    public function kembalian()
    {
        $id = $this->input->post('id');
        $order_data = $this->model_orders->getOrdersData($id);
        $hasil = $order_data['tunai'] - $order_data['gross_amount'];
        if ($hasil) {
            echo 'Rp.' . number_format($hasil, 0, ',', '.');
        } else {
            echo 'Lunas';
        }
    }

    public function history()
    {
        $result = array('data' => array());

        $store_id = $this->session->userdata('store_id');
        $data = $this->model_products->getkasir($store_id);

        foreach ($data as $key => $value) {

            $buttons = ' <div class="btn-group dropleft">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"> <span class="caret"></span></button>
            <ul class="dropdown-menu">';
            $buttons .= '<li><a href="#" onclick="lihatFunc(' . $value['id'] . ')"  data-toggle="modal" data-target="#lihatModal"><i class="fa fa-file-text-o"></i> Cek Order</a></li>';
            $buttons .= '<li><a href="#" onclick="print(' . $value['id'] . ')"><i class="fa fa-print"></i> Print Struk</a></li>';
            $buttons .= '</ul></div>';

            $result['data'][$key] = array(
                $buttons,
                $value['tgl_pesan'],
                '#' . $value['bill_no'],
                $value['customer_name'],
                number_format($value['net_amount'], 0, ",", "."),
                number_format($value['tunai'], 0, ",", "."),
            );
        } // /foreach

        echo json_encode($result);
    }


    public function updateharga()
    {

        $pesan = 'id ';

        $data = $this->model_products->getProductData();

        foreach ($data as $key => $value) {

            $hrgitem = $this->model_belanja->getbelanjaterimabyallid(mktime(0, 0, 0, date("n"), date("j") + 7, date("Y")), date('Y-m-d'), $value['id']);

            $jmlhrg = 0;

            foreach ($hrgitem as $h) {
                $cekbelanja = $this->model_belanja->getbelanjaData($h['belanja_id']);
                if ($cekbelanja['status'] && $h['harga']) {
                    $jmlhrg += $h['harga'];
                }
            }

            if ($jmlrow = count($hrgitem)) {
                $jl = ceil($jmlhrg / $jmlrow); //jumlah
                $persen = $jl * 0.1;
                $hargacek = $jl + $persen;
            } else {
                $hargacek = $jmlhrg;
            }




            ///untuk eksekusi
            $product_data = $this->model_products->getProductData($value['id']);
            if ($product_data['price'] == $hargacek) {
                $price = $product_data['price'];
                $harga = $product_data['price_old'];
                $tgl = $product_data['price_tgl'];
            } else {
                $tgl = date('Y-m-d');
                $harga = $product_data['price'];
                $price = $hargacek;
            }

            $data = array(
                'price' => $price,
                'price_old' => $harga,
                'price_tgl' => $tgl
            );

            $cek = $this->model_products->update($data, $value['id']);
            if (!$cek) {
                $pesan .= $product_data['name'] . ', ';
                $err = true;
            }
        }

        $pesan .= 'Terjadi Kegagalan';

        if (isset($err)) {
            $succes = false;
            $mess = $pesan;
        } else {
            $succes = true;
            $mess = 'Harga Berhasil di Update';
        }


        echo json_encode(
            [
                'pesan' => $mess,
                'success' => $succes
            ]
        );
    }
}
