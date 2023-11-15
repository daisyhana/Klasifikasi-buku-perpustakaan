<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
class C_klasifikasi extends CI_Controller
{
	function __construct(){
		parent::__construct();
		$this->load->model('m_klasifikasi');
	}

	public function index(){	
		$this->load->view('index');
	}

	public function c_loginAdmin(){
		$this->load->view('login_admin');
	} 

	public function c_homeAdmin(){
		$this->load->view('index_admin');
	}

	public function c_Login(){
		$username = $this->input->post('username');
		$password =  $this->input->post('password');
		$cek = array(
			'username' =>$username,
			'password' => $password
		); 
		if ($username == 'hana' && $password == 'taehyun'){
			$hasil = 1;
		} else { $hasil = 0; }

		if($hasil == 1) {
			$data_session = array(
				'nama' =>$username,
				'status' => "Login"
			);
			$this->session->set_userdata($data_session);
			redirect('/c_klasifikasi/c_homeAdmin');
		} else {
			$data_session = array(
				'nama' =>$username,
				'status' => "Gagal"
			);
			$this->session->set_userdata($data_session);
			redirect('/c_klasifikasi/c_loginAdmin');
		}
	}

	public function c_textpreprocessing($datalatih){ // 
		//ini untuk preprocessing data langsung banyak
		//ambil data latih
		// $dl=$this->m_klasifikasi->m_getdatatest();
		// $jmlh = count($dl);
		// // print_r($dl);
		// foreach ($dl as $data) {
		// 	$datalatih['no_kelasddc']=$data['no_kelasddc'];
		// 	$datalatih['judul_buku']= $data['judul_buku'];
		// 	$datalatih['pengarang'] = $data['pengarang'];
		// 	$datalatih['penerbit'] = $data['penerbit'];
		// 	$datalatih['level'] = $data['level'];

		$prepro['judul_buku'] = $datalatih['judul_buku'];
		$prepro['level'] =$datalatih['level'];
		// preprocessing 
		// Case Folding	
		$prepro['lower'] = strtolower(trim($datalatih['judul_buku']));
		$prepro['casefolding'] = preg_replace('/[0-9,\(\)\:\-\=\.\,\;\!\?]+/', '', $prepro['lower']);
		//Tokenizing
		$prepro['tokenizing'] = explode(" ", $prepro['casefolding']);
		$prepro['tokenstring'] = json_encode($prepro['tokenizing']);
		//filtering sastrawi
		$prepro['gabungtoken'] =  implode(" ", $prepro['tokenizing']);
		$stopWordRemoverFactory = new \Sastrawi\StopWordRemover\StopWordRemoverFactory();
		$stopword = $stopWordRemoverFactory->createStopWordRemover();
		$prepro['filtering'] = $stopword->remove($prepro['gabungtoken']);
		//stemming sastrawi
		$stemmerFactory= new \Sastrawi\Stemmer\StemmerFactory();
		$stemmer = $stemmerFactory->createStemmer();
		$prepro['stemming'] = $stemmer->stem($prepro['filtering']);

		//get last id_datalatih
		$prepro['id_datalatih']= $this->m_klasifikasi->m_inputdatalatih($datalatih);

		//ini untuk data banyak
			// $prepro['id_datalatih'] = $data['id_datalatih']; 
			
		// get last id_preprocessing
		$idPreprocessing['id_preprocessing'] = $this->m_klasifikasi->m_preprocessing($prepro); 
		return $prepro;
		// } //untuk data banyak	
	}

	public function c_preproDataLatih(){
		//id yang mulai dihapus dari 105 dst oke !!!
		$datalatih = array(
			'no_kelasddc' => $this->input->post('no_kelasddc'),
			'judul_buku' => $this->input->post('judul_buku'),
			'pengarang' => $this->input->post('pengarang'),
			'penerbit' => $this->input->post('penerbit'),
			'level' => 'latih'
		);

		$alldata = $this->m_klasifikasi->m_cekdatainput($datalatih['judul_buku']);
		if ($alldata == 1){
			$dataPopUp = $this->m_klasifikasi->m_getdataPopUp($datalatih['judul_buku']);
			$json = array(
				"is_available" => true,
				"data" => array(
					"kelas_ddc" => $dataPopUp[0]["no_kelasddc"],
					"judul_buku" => $dataPopUp[0]["judul_buku"],
					"pengarang" => $dataPopUp[0]["pengarang"],
					"penerbit" => $dataPopUp[0]["penerbit"]
				)
			);
			echo json_encode($json);
		} else {
			$prepro = $this->c_textpreprocessing($datalatih);
			$term= explode(' ', $prepro['stemming']); // memisahkan string menjadi array
			$this->c_termcount($term,$prepro['id_datalatih']);
		// $this->c_tfidf();// load fungsi tfidf dulu buat ngitung term di tabel preprocessing
			$prepro['bterm'] = $this->c_termweight($prepro['id_datalatih']);
			$prepro['bdoc'] = $this->c_docweight1($prepro['id_datalatih']);
			$this->load->view('v_textmining',$prepro);
		}
	}

	public function c_preproDataUji(){
		$data=array(
			'judul_buku' =>$this->input->post('judul_buku'),
			'pengarang' => $this->input->post('pengarang'),
			'penerbit' => $this->input->post('penerbit'),
			'level' => 'uji'
		);

		$alldata = $this->m_klasifikasi->m_cekdatainput($data['judul_buku']);
		if ($alldata == 1){
			$dataPopUp = $this->m_klasifikasi->m_getdataPopUp($data['judul_buku']);
			$json = array(
				"is_available" => true,
				"data" => array(
					"kelas_ddc" => $dataPopUp[0]["no_kelasddc"],
					"judul_buku" => $dataPopUp[0]["judul_buku"],
					"pengarang" => $dataPopUp[0]["pengarang"],
					"penerbit" => $dataPopUp[0]["penerbit"]
				)
			);
			echo json_encode($json);
			
		} else {
					
			$datauji = $this->c_textpreprocessing($data);
			$datauji['pengarang'] = $data['pengarang'];
			$datauji['penerbit'] = $data['penerbit'];

			$term= explode(' ', $datauji['stemming']); // memisahkan string
			$this->c_termcount($term,$datauji['id_datalatih']);

			$datauji['bterm'] = $this->c_termweight($datauji['id_datalatih']);
			$datauji['bdoc'] = $this->c_docweight1($datauji['id_datalatih']);
			$datauji['term'] = $this->m_klasifikasi->m_getTermbyID($datauji['id_datalatih']);
			$datauji['jmlh'] = count($datauji['term']);

			$this->c_NaiveBayes1(); // hitung probabilitas semua kelas ddc 
			$datauji['murni'] = $this->c_KlasifikasiMurni($datauji['term'],$datauji['jmlh']);
			$datauji['gabungan'] = $this->c_KlasifikasiGabungan($datauji['term'],$datauji['jmlh']);

		//update kelas ddc untuk data uji
			$this->m_klasifikasi->m_updatekelasDU($datauji['id_datalatih'],$datauji['gabungan']['NBCgabungan']);
			
		//input data untuk tabel nbc murni dan nbc gabungan
			$this->m_klasifikasi->m_inputDUmurni($datauji['murni']['NBCmurni'], $data['judul_buku'], $data['pengarang'],$data['penerbit'],$datauji['murni']['time']);
			$this->m_klasifikasi->m_inputDUgab($datauji['gabungan']['NBCgabungan'], $data['judul_buku'], $data['pengarang'],$data['penerbit'], $datauji['gabungan']['time']);

		//ini diisi no ddc hasil klasifikasi
			$datauji['kelasddc'] =$this->m_klasifikasi->m_getkelasDDC($datauji['gabungan']['NBCgabungan']);
			$datauji['kelasddcm'] = $this->m_klasifikasi->m_getkelasDDC($datauji['murni']['NBCmurni']);
			$datauji['bukuDDC']= $this->m_klasifikasi->m_getbukuDDC($datauji['gabungan']['NBCgabungan']);

			if($this->session->userdata('status')!='Login'){
				$this->load->view('v_hasilklasifikasi',$datauji);
			} else{
				$this->load->view('v_hasilKlasifikasiAdmin',$datauji);
			} 
		}

	}

//coba hitung tfidf 1 data
	public function c_termcount($term,$id){
		$n = count($term);
		for($j=0; $j<$n; $j++) {
			if ($term[$j] != ""){ // jika term tidak kosong
				$rescount = $this->m_klasifikasi->m_counttbindex($term[$j],$id); //field count term ini itu berapa kalok 0 berarti term blm ada kalok 1 berarti term udah ada trus +1
				$num_rows = count($rescount);
				if ($num_rows > 0) {
					foreach ($rescount as $rowcount) {
						$count = $rowcount['Count'];
						$count++;
						$this->m_klasifikasi->m_updatetbindex($count,$term[$j],$id);
					}						
				} else {
					$countnumber = 1;
					$dataterm = $this->m_klasifikasi->m_inserttbindex($term[$j],$id,$countnumber);
				}
			}
		}
	}
//sampek sini

//ini untuk hitung data banyak
	// public function c_tfidf(){
	// 	//ini fungsi untuk ngitung ada berapa banyak term dalam suatu dokumen
	// 	$this->m_klasifikasi->m_deltbindex();
	// 	$alldatalatih = $this->m_klasifikasi->m_getpreprocessing();
	// 	foreach ($alldatalatih as $row) {
	// 		$docid =  $row['id_datalatih'];
	// 		$datastemming = $row['stemming'];
	// 		$term = explode(" ", trim($datastemming));

	// 		foreach ($term as $j => $value) {
	// 			if ($term[$j] != ""){
	// 				$rescount = $this->m_klasifikasi->m_counttbindex($term[$j],$docid); 
	// 				$num_rows = count($rescount);
	// 				if ($num_rows > 0) {
	// 					foreach ($rescount as $rowcount) {
	// 						$count = $rowcount['Count'];
	// 						$count++;
	// 						$this->m_klasifikasi->m_updatetbindex($count,$term[$j],$docid);
	// 					}						
	// 				} else {
	// 					$countnumber = 1;
	// 					$dataterm = $this->m_klasifikasi->m_inserttbindex($term[$j],$docid,$countnumber);
	// 				}
	// 			}
	// 		}	
	// 	} 
	// }

	public function c_termweight($id_datalatih){ //
		// $resn = $this->m_klasifikasi->m_getalldoc();
		$n = $this->m_klasifikasi->m_countalldoc(); // hitung banyaknya data 
		$resbobot = $this->m_klasifikasi->m_gettbindex(); // ambil seluruh term
		foreach ($resbobot as $rowbobot) {
			$term = $rowbobot['Term'];
			$tf = $rowbobot['Count']; // nilai di field count sebagai tf
			$id = $rowbobot['Id'];
			//N = banyak dokumen yang mengandung term
			$resNTerm = $this->m_klasifikasi->m_countNTerm($term); // hitung si term ini ada berapa di tabel tbindex, 
			$docweight = 0;
			foreach ($resNTerm as $rowNTerm) {
				$NTerm = $rowNTerm['N']; //nilai df
				 //bobot term
				$w = ($tf * log($n/$NTerm)); //hitung IDF
				//update bobot term
				$this->m_klasifikasi->m_updateTermWeight($id,$w);
			}
		}
		$termweight = $this->m_klasifikasi->m_getTermWeightbyID($id_datalatih);
		return $termweight; //mengembalikkan bobot term yang dimiliki si id
	}

	public function c_docweight1($id_datalatih){
		$resVektor = $this->m_klasifikasi->m_getTermWeight($id_datalatih); // bobot term si id dari tbindex
		$amount = 0;
		foreach ($resVektor as $rowVektor) {
			$amount = $amount + $rowVektor['Bobot']; // hitung bobot dokumen
			$this->m_klasifikasi->m_updateDocWeight($id_datalatih,$amount); //update bobot dokumen
		}
		// $docweight = $this->m_klasifikasi->m_getDocWeightbyID($id_datalatih);
		return $amount;
	}

	// public function c_docweight(){ //$id_datalatih
	// 	//menghitung bobot setiap dokumen
	// 	$resDocId = $this->m_klasifikasi->m_getalldoc();
	// 	foreach ($resDocId as $rowDocId) {
	// 		$docId = $rowDocId['DocId'];
	// 		$resVektor = $this->m_klasifikasi->m_getTermWeight($docId);
	// 		$amount = 0;
	// 		foreach ($resVektor as $rowVektor) {
	// 			$amount = $amount + $rowVektor['Bobot'];
	// 			$this->m_klasifikasi->m_updateDocWeight($docId,$amount);
	// 		}
	// 	}
	// // 	$docweight = $this->m_klasifikasi->m_getDocWeightbyID($id_datalatih);
	// // 	return $docweight;
	// }
// sampek sini

	public function c_Medoids(){
		$data = $this->m_klasifikasi->m_getDocWeight(); // ambil bobot dokumen
		$jmlhdata = count($data); //hitung banyak nya data yang digunakan
		for ($i=0; $i<$jmlhdata;$i++){ //inisialisasi cluster awal untuk perbandingan
			$clusterAwal[$i] = 'C1';
		}
		do { // siapkan variabel untuk medoid sebanyak cluster
			$medoid1 = $this->m_klasifikasi->m_getRandomData();
			$medoid2 = $this->m_klasifikasi->m_getRandomData();
			$medoid3 = $this->m_klasifikasi->m_getRandomData(); 
			if ($medoid1[0]['id_datalatih'] != $medoid2[0]['id_datalatih'] && $medoid1[0]['id_datalatih'] != $medoid3[0]['id_datalatih'] && $medoid2[0]['id_datalatih'] != $medoid3[0]['id_datalatih']){
				$random = 'true';
			} else {
				$random = 'false';
			}
		}while ($random == ' false');

		do{ 
			$wakilmedoid = array($medoid1,$medoid2,$medoid3); 
			$this->m_klasifikasi->m_delmedoid();//simpan dulu data medoidnya di db
			for($i=0; $i<3;$i++){
				$kelasddcWM = $this->m_klasifikasi->m_getkelasWM($wakilmedoid[$i][0]['id_datalatih']);
				$this->m_klasifikasi->m_insertmedoid($i+1,$wakilmedoid[$i][0]['id_datalatih'],$kelasddcWM[0]['no_kelasddc']);
			}
			$simpangan = 0; //variabel untuk menghitung simpangan total
			$x = 0;	$xn = 0; //variabel untuk menandai iterasi ke berapa
		//sediakan variabel untuk nilai jarak terkecil medoids
			$jarakKecil1 = 0; $jarakKecil2 = 0; $jarakKecil3 = 0;
			foreach ($data as $d) { //untuk meyimpan hasil cluster awal;
			//deklarasi variabel untuk hasil perhitungan jarak dengan euclidean distance
				$jarak1=0; $jarak2=0; $jarak3=0;
			//hitung euclidean distance
				$jarak1 = abs((float)$d['bobot_dokumen'] - $medoid1[0]['bobot_dokumen']);
				$jarak2 = abs((float)$d['bobot_dokumen'] - $medoid2[0]['bobot_dokumen']);
				$jarak3 = abs((float)$d['bobot_dokumen'] - $medoid3[0]['bobot_dokumen']);
			//hitung jumlah jarak terkecil 
				if ($jarak1 < $jarak2 && $jarak1 < $jarak3) {
					$jarakKecil1 = $jarakKecil1 + $jarak1;
				//menyimpan cluster dokumen sementara;
					$clusterAkhir[$x] = addslashes('C1');
					$id_data[$x] = $d['id_datalatih'];
					$this->m_klasifikasi->m_updateClusterData($id_data[$x],$clusterAkhir[$x]);
				}
				else if ($jarak2<$jarak1 && $jarak2<$jarak3){
					$jarakKecil2 = $jarakKecil2 + $jarak2;
					$clusterAkhir[$x] = addslashes('C2');
					$id_data[$x] = $d['id_datalatih'];
					$this->m_klasifikasi->m_updateClusterData($id_data[$x],$clusterAkhir[$x]);
				}
				else {
					$jarakKecil3 = $jarakKecil3 + $jarak3;
					$clusterAkhir[$x] = addslashes('C3');
					$id_data[$x] = $d['id_datalatih'];
					$this->m_klasifikasi->m_updateClusterData($id_data[$x],$clusterAkhir[$x]);
				}
				$x +=1;
			}//akhir foreach medoid
			//menghitung nilai cost 1
			$cost1 = 0;
			$cost1 = $jarakKecil1 + $jarakKecil2 + $jarakKecil3;
			$nonrandom = 'false';
			//perhitungan non medoids
				do { // data non medoids tidak boleh sama dengan data medoid untuk semua cluster
					$nonmedoid1 = $this->m_klasifikasi->m_getRandomc1();
					$nonmedoid2 = $this->m_klasifikasi->m_getRandomc2();
					$nonmedoid3 = $this->m_klasifikasi->m_getRandomc3();
					if ($nonmedoid1[0]['id_datalatih'] != $medoid1[0]['id_datalatih'] && $nonmedoid2[0]['id_datalatih'] != $medoid2[0]['id_datalatih'] && $nonmedoid3[0]['id_datalatih'] !=  $medoid3[0]['id_datalatih']){
						if ($nonmedoid1[0]['id_datalatih'] != $nonmedoid2[0]['id_datalatih'] && $nonmedoid1[0]['id_datalatih'] != $nonmedoid3[0]['id_datalatih'] && $nonmedoid2[0]['id_datalatih'] != $nonmedoid3[0]['id_datalatih']){
							$nonrandom = 'true';
						} else {
							$nonrandom = 'false';
						}
					}
				} while($nonrandom == 'false');

				$nonJarakKecil1 = 0; $nonJarakKecil2 = 0; $nonJarakKecil3 = 0;
				foreach ($data as $dn) {
					$jaraknon1=0;
					$jaraknon3=0;
					$jaraknon2=0;
		 	//hitung euclidean distance. karena hanya satu atribut maka perhitungan euclidean distance hanya seperti pengurangan.
					$jaraknon1 = abs((float)$dn['bobot_dokumen'] - $nonmedoid1[0]['bobot_dokumen']);
					$jaraknon2 = abs((float)$dn['bobot_dokumen'] - $nonmedoid2[0]['bobot_dokumen']);
					$jaraknon3 = abs((float)$dn['bobot_dokumen'] - $nonmedoid3[0]['bobot_dokumen']);

					if($jaraknon1<$jaraknon2 && $jaraknon1<$jaraknon3){
						$nonJarakKecil1 = $nonJarakKecil1 + $jaraknon1;
						$nonclusterAkhir[$xn] = addslashes('C1');
						$nonid_data[$xn] = $dn['id_datalatih'];
					}
					else if($jaraknon2<$jaraknon1 && $jaraknon2<$jaraknon3){
						$nonJarakKecil2 = $nonJarakKecil2 + $jaraknon2;
						$nonclusterAkhir[$xn] = addslashes('C2');
						$nonid_data[$xn] = $dn['id_datalatih'];
					}
					else {
						$nonJarakKecil3 = $nonJarakKecil3 + $jaraknon3;
						$nonclusterAkhir[$xn] = addslashes('C3');
						$nonid_data[$xn] = $dn['id_datalatih'];
					}
					$xn +=1;
				} // akhir foreach non medoids
			// akhir perhitungan non medoid
				$cost2 = $nonJarakKecil1 + $nonJarakKecil2 + $nonJarakKecil3; 
				//hitung simpangan
				$simpangan = $cost2 - $cost1; 
				$clusterdata = array(); 

				if ($simpangan <  0 ){
					$medoid1 = $nonmedoid1; $medoid2 = $nonmedoid2; $medoid3 = $nonmedoid3;

					$nonmedoid1 = array (); $nonmedoid2 = array (); $nonmedoid3 = array ();
			//maka update anggota clusternya pake yang non medoid
					for($i=0; $i<$jmlhdata; $i++){
						$this->m_klasifikasi->m_updateClusterData($nonid_data[$i],$nonclusterAkhir[$i]);
						array_push($clusterdata, $nonclusterAkhir[$i]);
					}
				}
				else if ($simpangan >= 0) {
			// maka update clusternya pake yang medoid
					for($i=0; $i<$jmlhdata; $i++){
						$this->m_klasifikasi->m_updateClusterData($id_data[$i],$clusterAkhir[$i]);
						array_push($clusterdata, $clusterAkhir[$i]);
					}
		}//akhir else
		for ($i =0; $i< $jmlhdata; $i++){
			if ($clusterAwal[$i] != $clusterdata[$i]){
				$status = 'false';
			} else {
				$status = 'true';
			}
		}
		if($status == 'false'){
			$clusterAwal = $clusterdata;
		}
	} while($status == 'false'); //akhir while
	$hasilmedoid = $this->m_klasifikasi->m_getcluster();
	return $hasilmedoid;
}

public function c_cekClustermedoid($term){ //$term
	$n=count($term); // hitung banyaknya term du
	for ($i=0; $i <$n ; $i++) { 
		$UsedTerm=$term[$i]['Term']; 
	}
	$fixTerm=explode(' ', $UsedTerm); // misahin term du jadi array
	$termcluster = $this->m_klasifikasi->m_getDataMedoid(); //get data wakil medoid+klmpk
	$m = count($termcluster); //hitung banyak data medoid(3)
	$cekCount =array();
	for($i=0;$i<$m;$i++){ //perulangan sebanyak 3
		$termMedoid=explode(' ', $termcluster[$i]['stemming']); //misahin term dm
		$same = array_intersect($fixTerm,$termMedoid); // ada gak term yg sama
		$n = count($same); //ngecek banyak term yg sama
		if ($n==null){ $n=0; } //kalok null berarti nggak sama
		$dataArrayP=['cluster'=>$termcluster[$i]['cluster'],'n'=>$n];
		array_push($cekCount, $dataArrayP); //hasil perbandingan 3 dm dengan du simpan disini
	}
	$clusterMax = max(array_column($cekCount,'n'));	//ambil data dengan perbandingan terbesar
	if($clusterMax==0){ //kalok nggak ada terbesar
		$UsedCluster = $this->m_klasifikasi->m_getketCluster(); //ambil data medoid dgn prob terbesar
		$senddata = $this->m_klasifikasi->m_getDatabyCluster($UsedCluster[0]['keterangan']); // ambil dl yg klmpk == usedcluster
	} else { //ada terbesar
		$UsedCluster = array_search($clusterMax, array_column($cekCount,'n')); //cari dm ini klmpk brp
		$UsedCluster1 = $cekCount[$UsedCluster]['cluster']; //si ket kelompok
		$senddata = $this->m_klasifikasi->m_getDatabyCluster($UsedCluster1); //ambil dl berdasar klmpk usedcluster1
	}
	return $senddata; //kirim data ke fungsi klasifikasi gabungan
}

public function c_KlasifikasiMurni($term,$jmlh){
	$awal = microtime(true);
	$kelasddc=$this->m_klasifikasi->m_getallkelas();
	$termDU = $term; 
	$jmlhtermDU = $jmlh;
	$data['NBCmurni'] = $this->c_NaiveBayes2($termDU,$jmlhtermDU,$kelasddc);
	$akhir= microtime(true);
	$waktueksekusi = $akhir-$awal;
	$data['time'] = $waktueksekusi * 0.000001; // dalam satuan detik
	return $data;
}

public function c_KlasifikasiGabungan($term,$jmlh){
	$awal = microtime(true);
	$kelasddc = $this->c_cekClustermedoid($term);
	$termDU = $term;
	$jmlhtermDU = $jmlh;
	$data['NBCgabungan'] = $this->c_NaiveBayes2($termDU,$jmlhtermDU,$kelasddc);
	$akhir=microtime(true);
	$waktueksekusi = $akhir-$awal; // dalam satuan mikrodetik
	$data['time'] = $waktueksekusi*0.000001; //dalam satuan detik
	return $data;
}

public function c_NaiveBayes1(){
	//kosongkan tabel peluang kelas ddc
	$this->m_klasifikasi->m_delpeluangddc();
	//ambil data latih
	$datalatih = $this->m_klasifikasi->m_getDataLatih();
	$jmlhdata = count($datalatih);
	//hitung probabilitas kelas DDC
	foreach ($datalatih as $k) {
		$kelasDDC = $k['no_kelasddc'];
		if($kelasDDC != "" ){
			$rescount =$this->m_klasifikasi->m_countpeluangddc($kelasDDC); //ini select data
			$num_rows = count($rescount);
			if($num_rows > 0){
				foreach ($rescount as $rowcount) {
					$count = $rowcount['count'];
					$count++;
					$this->m_klasifikasi->m_updatepeluangddc($count, $kelasDDC);
					$this->m_klasifikasi->m_updatePDDCMedoids($count, $kelasDDC);
				}
			}
			else {
				$countnumber = 1;
				$datakelas = $this->m_klasifikasi->m_insertpeluangddc($kelasDDC,$countnumber);
			}
		}
	}
	$countkelas = $this->m_klasifikasi->m_getallkelas();
	foreach ($countkelas as $prob) {
		$probkelas = $prob['count'] / $jmlhdata;
		$this->m_klasifikasi->m_updateKelas($prob['id'],$probkelas); 
		$this->m_klasifikasi->m_updatekelasmedoids($prob['no_kelas'],$probkelas);
		} // akir perhitungan probabilitas kelas
	}

	public function c_NaiveBayes2($termDU,$jmlhtermDU,$kelasddc){
		//hitung probabilitas data uji
		$contohdatauji = $termDU;
		//hitung banyak term data uji
		$m = $jmlhtermDU;
		
		//ambil kelas ddc
		$getkelasddc = $kelasddc;
		foreach ($getkelasddc as $k) {
			$peluangkelas = $k['probabilitas'];
			$noKelas = $k['no_kelas'];
			$mp = $m * $peluangkelas;
			//cari jumlah term yang mengandung kelas ddc
			$nArray = $this->m_klasifikasi->m_jmlhTermKelas($noKelas);
			$n = $nArray[0]['SUM(Count)']; // hitung banyak term yg punye kelas ddc nokelas
			$nm = $n + $m;
			//cari jumlah term yang sama dengan term data uji berdasarkan kelas ddc / nc
			$cek=array();
			$totalPterm = 1;
			$termkelas = $this->m_klasifikasi->m_getAllTerm($noKelas);
			for($i=0;$i<$m;$i++){
				foreach ($termkelas as $k) {
					array_push($cek, strcmp($k['Term'], $contohdatauji[$i]['Term']));
				} 
				if(in_array(0, $cek)){
					$nc[$i]= 1; //berarti di dalam kelas ddc tersebut ada term yang sama dgn data uji
				} else {
					$nc[$i]= 0;
				}	
				$cek=array();
				$NcMp[$i] = $nc[$i] + $mp;
				$PTerm[$i] = $NcMp[$i] / $nm;
				$totalPterm *= $PTerm[$i];
			} 
			//hitung peluang untuk data uji diprediksi sbg kelas ddc
			$PkelasDU = $peluangkelas * $totalPterm;
			$this->m_klasifikasi->m_updatePkelasDU($PkelasDU,$noKelas);
		} 
		// hitung nilai max dari peluang
		$getpeluang = $this->m_klasifikasi->m_getPkelasDU(); 
		$nilai = $getpeluang[0]['probkelasDU'];

		// cari kelas yang memiliki nilai max, kesimpulan kelas yang akan menjadi kelas ddc dari data uji
		$getkelas = $this->m_klasifikasi->m_cariKelasDU($nilai);
		$kelasAkhir = $getkelas[0]['no_kelas'];

		return $kelasAkhir;
	}

	// public function c_NaiveBayes2(){ //$termDU,$jmlhtermDU,$kelasddc
		//hitung probabilitas data uji
		// $contohdatauji = $termDU;
		// $contohdatauji = array("hikmah","atlas","indonesia","dunia","versi","benua","asia");
		// print_r($contohdatauji);
		//hitung banyak term data uji
		// $m = $jmlhtermDU;
		// $m=count($contohdatauji);
		//ambil kelas ddc
		// $getkelasddc = $kelasddc;
		// $getkelasddc= $this->m_klasifikasi->m_getallkelas();
		// print_r($getkelasddc);
		// foreach ($getkelasddc as $k) {
		// 	$peluangkelas = $k['probabilitas'];
		// 	if (count($getkelasddc)!=3){
		// 		$noKelas = $k['no_kelas'];
		// 	}else {
		// 		$noKelas = $k['no_kelasddc'];
		// 	}
		// 	$mp = $m * $peluangkelas;
			//cari jumlah term yang mengandung kelas ddc
			// $nArray = $this->m_klasifikasi->m_jmlhTermKelas($noKelas);
			// $n = $nArray[0]['SUM(Count)'];
			// $nm = $n + $m;
			// //cari jumlah term yang sama dengan term data uji berdasarkan kelas ddc / nc
			// $cek=array();
			// $totalPterm = 1;
			// $termkelas = $this->m_klasifikasi->m_getAllTerm($noKelas); //ambil term berdasar kelas ddc. 
			// $fixTerm = array_column($termkelas,'Term');
			// $sameTerm= array_intersect($contohdatauji, $fixTerm);
			// $jmlh = count($sameTerm);
			// if ($jmlh == null){
			// 	$jmlh =0;
			// }
			// $NcMp = $jmlh +$mp;
			// $PTerm = $NcMp / $nm;
			// $totalPterm *= $PTerm;

			//ini yg mau diganti
			// for($i=0;$i<$m;$i++){
			// $sameTerm= array_intersect($fixTerm,$contohdatauji[$i]);// diulang sebanyak term data uji
			// $jmlh[$i] = count($sameTerm);
			// if ($jmlh[$i] == null){
			// 	$jmlh[$i] = 0;
			// }
			// $NcMp[$i] = $jmlh[$i] + $mp;
			// $PTerm[$i] = $NcMp[$i] / $nm;
			// $totalPterm *= $PTerm[$i];
			// 	foreach ($termkelas as $k) { //intinya disini itu hasil ceknya masih 0
			// 		// array_push($cek, strcmp($k['Term'], $contohdatauji[$i]['Term']));
			// 		array_push($cek, strcmp($k['Term'], $contohdatauji[$i]));
			// 	}
			// 	if(in_array(0, $cek)){
			// 		$nc[$i]= 1; //berarti di dalam kelas ddc tersebut ada term yang sama dgn data uji
			// 	} else {
			// 		$nc[$i]= 0;
			// 	}	
			// 	$cek=array();
			// 	$NcMp[$i] = $nc[$i] + $mp;
			// 	$PTerm[$i] = $NcMp[$i] / $nm;
			// 	$totalPterm *= $PTerm[$i];
			// } 
			//sampe sini

			//ini yg nanti di uncoment
			//hitung peluang untuk data uji diprediksi sbg kelas ddc
		// 	$PkelasDU = $peluangkelas * $totalPterm;
		// 	if (count($getkelasddc)!=3){
		// 		$this->m_klasifikasi->m_updatePkelasDU($PkelasDU,$noKelas);
		// 	} else {
		// 		$this->m_klasifikasi->m_updatePkelasDUM($PkelasDU,$noKelas);
		// 	}

		// } 

		// if (count($getkelasddc)!=3){
		// 	$getpeluang = $this->m_klasifikasi->m_getPkelasDU(); 
		// 	$nilai = $getpeluang[0]['probkelasDU'];
		// // cari kelas yang memiliki nilai max, kesimpulan kelas yang akan menjadi kelas ddc dari data uji
		// 	$getkelas = $this->m_klasifikasi->m_cariKelasDU($nilai);
		// 	$kelasAkhir = $getkelas[0]['no_kelas'];
		// } else {
		// 	$getpeluang = $this->m_klasifikasi->m_getPkelasDUM(); 
		// 	$nilai = $getpeluang[0]['probkelasDU'];
		// // cari kelas yang memiliki nilai max, kesimpulan kelas yang akan menjadi kelas ddc dari data uji
		// 	$getkelas = $this->m_klasifikasi->m_cariKelasDUM($nilai);
		// 	$kelasAkhir = $getkelas[0]['no_kelasddc'];
		// }
		// return $kelasAkhir;
	// }

	public function c_inputdatalatih(){
		$this->load->view('v_inputdatalatih');
	}

	public function c_inputdatauji(){
		$this->load->view('v_inputdatauji');
	}

	public function c_clustering(){
		$d['buku'] =$this->m_klasifikasi->m_getBukunBobot();
		$d['medoid'] = $this->m_klasifikasi->m_getMedoid();
		$this->load->view('v_clustering',$d);		
	}

	public function c_hasilclustering(){
		$this->c_Medoids();
		$d['cluster'] = $this->m_klasifikasi->m_getNilaiCluster();
		$d['c1'] = $this->m_klasifikasi->m_countC1();
		$d['c2'] = $this->m_klasifikasi->m_countC2();
		$d['c3'] = $this->m_klasifikasi->m_countC3();
		$d['wakilmedoid'] = $this->m_klasifikasi->m_getwakilMedoid();
		$this->load->view('v_hasilclustering',$d);
	}

	public function c_klasifikasibuku(){
		$this->load->view('v_klasifikasibuku');
	}

	public function c_klasifikasiUser(){
		$this->load->view('v_klasifikasiUser');
	}

	public function c_databukuUser(){
		$from = $this->uri->segment(3);		
		$databukuUser['databukuUser'] = $this->m_klasifikasi->m_databukuUser();
		$config['base_url'] = base_url().'index.php/c_klasifikasi/c_databukuUser';
		$config['total_rows'] = $databukuUser['databukuUser'];
		$config['per_page'] = 10;
		$config['next_link'] = 'Selanjutnya';
		$config['prev_link'] = 'Sebelumnya';
		$config['first_link'] = 'Awal';
		$config['last_link'] = 'Akhir';
		$config['full_tag_open'] = '<div class="pagging text-center"><nav><ul class="pagination justify-content-center">';
		$config['full_tag_close'] = '</ul></nav></div>';
		$config['num_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['num_tag_close'] = '</span></li>';
		$config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">';
		$config['cur_tag_close'] = '<span class="sr-only">(current)</span></span></li>';
		$config['prev_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['prev_tagl_close'] = '</span>Selanjutnya</li>';
		$config['next_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['next_tagl_close'] = '<span aria-hidden="true">&raquo;</span></span></li>';
		$config['last_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['last_tagl_close'] = '</span></li>';
		$config['first_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['first_tagl_close'] = '</span></li>';

		$this->pagination->initialize($config);
		$judul = $this->input->post('judul');
		$databukuUser['pagination_buku'] = $this->m_klasifikasi->pagination_buku($config['per_page'],$from,$judul);
		$databukuUser['pagination'] = $this->pagination->create_links();
		$this->load->view('v_databukuUser',$databukuUser);
	}

	public function c_akurasimetode(){
		$d['latih'] = $this->m_klasifikasi->m_countdatalatih();
		$d['uji'] = $this->m_klasifikasi->m_countdatauji();
		$this->load->view('v_akurasi',$d);
	}

	public function c_datakelasddc(){
		$pilih = $this->input->post('data_master');
		$data['pilihan'] = intval($pilih);

		$senddata['no'] =$pilih;	
		$senddata['datanya'] = $this->m_klasifikasi->m_datamaster($data['pilihan']);
		$from = $this->uri->segment(3);
		$config['base_url'] = base_url().'index.php/c_klasifikasi/c_datakelasddc';
		$config['total_rows'] = $senddata['datanya'];
		$config['per_page'] = 10;
		$config['next_link'] = 'Selanjutnya';
		$config['prev_link'] = 'Sebelumnya';
		$config['first_link'] = 'Awal';
		$config['last_link'] = 'Akhir';
		$config['full_tag_open'] = '<div class="pagging text-center"><nav><ul class="pagination justify-content-center">';
		$config['full_tag_close'] = '</ul></nav></div>';
		$config['num_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['num_tag_close'] = '</span></li>';
		$config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">';
		$config['cur_tag_close'] = '<span class="sr-only">(current)</span></span></li>';
		$config['prev_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['prev_tagl_close'] = '</span>Selanjutnya</li>';
		$config['next_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['next_tagl_close'] = '<span aria-hidden="true">&raquo;</span></span></li>';
		$config['last_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['last_tagl_close'] = '</span></li>';
		$config['first_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['first_tagl_close'] = '</span></li>';

		$this->pagination->initialize($config);
		$senddata['paginationnya'] = $this->m_klasifikasi->pagination_datamaster($config['per_page'],$from,$data['pilihan']);
		$senddata['pagination'] = $this->pagination->create_links();
		$this->load->view('v_datakelasddc',$senddata);
	}

	public function c_akurasi2metode(){
		$murni = 'murni';
		$gab = 'gabungan';

		$akr['latih'] = $this->m_klasifikasi->m_countdatalatih();
		$akr['uji'] = $this->m_klasifikasi->m_countdatauji();
		$akr['murni'] = $this->c_akurasi($murni);
		$akr['gabungan'] = $this->c_akurasi($gab);

		// print_r($akr);
		$this->load->view('v_akurasi',$akr);
	}

	public function c_akurasi($metode){
		//kosongin dulu tabel confusion nya
		$this->m_klasifikasi->m_delconfusion();
		//get kelas ddc yang ada di data latih
		$datauji= $this->m_klasifikasi->m_countdatauji(); //untuk hitung AVG

		if (strcmp($metode,'murni')==0){
			$kelas = $this->m_klasifikasi->m_getpreNBCmurni(); //distinct kelas ddc pre
			$time = $this->m_klasifikasi->m_gettimeNBCmurni();//get waktu pre
			$jmlh = count($kelas); 
			$d['AVGtime'] = $time[0]['SUM(waktu_eksekusi)'] / $datauji[0]->du;//rata2 waktu eksekusi nbc murni
			$dataprediksi = $this->m_klasifikasi->m_getdataNBCmurni(); 
		} 
		else if (strcmp($metode,'gabungan')==0){
			$kelas = $this->m_klasifikasi->m_getpreNBCgab();
			$time = $this->m_klasifikasi->m_gettimeNBCgab();
			$jmlh = count($kelas);
			$d['AVGtime'] = $time[0]['SUM(waktu_eksekusi)'] / $datauji[0]->du; //rata2 waktu eksekusi nbc gabungan
			$dataprediksi = $this->m_klasifikasi->m_getdataNBCgab(); 
		}
		// $kelas = $this->m_klasifikasi->m_getkelasprediksi();
		// $jmlh = count($kelas);
		for ($i=0; $i<$jmlh ; $i++) { 
			$this->m_klasifikasi->m_ddcconfusion($kelas[$i]['no_kelasddc'],$jmlh);
		}

		// ambil data latih level uji dan data aktual
		// $dataprediksi = $this->m_klasifikasi->m_getdataLU(); 
		$dataaktual = $this->m_klasifikasi->m_getdataaktual();

		//hitung tp dan fn
		foreach ($dataprediksi as $k) {
			$tp=0; $fn=0;
			$kelaspred = $k['no_kelasddc'];
			$judulpre = $k['judul_buku'];
			foreach ($dataaktual as $m) {
				$kelasakt = $m['no_kelasddc'];
				$judulakt = $m['judul_buku'];
				if(strcmp($judulpre,$judulakt)==0){
					if ($kelaspred == $kelasakt){
					//kalok sama berarti tp, cek tp si kelas ddc ini null atau ada isinya
						$cek = $this->m_klasifikasi->m_getTP($kelaspred);
						if($cek[0]['TP'] == NULL){
							$tp = 1;
						}else {
							$tp = $cek[0]['TP']+1;
						}
						$this->m_klasifikasi->m_updateTP($kelaspred,$tp);
					} else {
					//get fn data dengan kelas ddc
						$cek = $this->m_klasifikasi->m_getFN($kelaspred);
						if ($cek[0]['FN'] == NULL){
							$fn = 1;
						} else {
							$fn = $cek[0]['FN'] + 1;
						}
						$this->m_klasifikasi->m_updateFN($kelaspred,$fn);
					}
				}
			}
		}
		//hitung fp
		foreach ($dataaktual as $k) {
			$fp=0;
			$kelasakt = $k['no_kelasddc'];
			$judulakt = $k['judul_buku'];
			foreach ($dataprediksi as $m) {
				$kelaspre = $m['no_kelasddc'];
				$judulpre = $m['judul_buku'];
				if(strcmp($judulakt,$judulpre)==0){
					if ($kelasakt != $kelaspre){
					//kalok sama berarti tp, cek tp si kelas ddc ini null atau ada isinya
						$cek = $this->m_klasifikasi->m_getFP($kelasakt);
						if ($cek == NULL){ // kelas data aktual nggak ada di prediksi, jadi di input di condusion matrix tapi nilai semuanya 0
							$fn=0; $tp =0; $fpakt=0;
							$this->m_klasifikasi->m_inputaktnotexist($kelasakt,$tp,$fn,$fpakt);
						} else if($cek[0]['FP'] == NULL){
							$fp = 1;
						}else {
							$fp = $cek[0]['FP']+2;
						}
						$this->m_klasifikasi->m_updateFP($kelaspred,$fp);
					}
				}
			}
		}

		//hitung akurasi
		$datamatrix = $this->m_klasifikasi->m_getconfusionmatrix();
		$jmlhDU = count($dataprediksi); //hitung banyak dp di tabel nbc murni/gab
		$jmlhddc = count($datamatrix); //hitung banyak kelas ddc di cm

		//akurasi
		$allTP = $this->m_klasifikasi->m_sumTP(); //jumlahkan seluruh ni tp di cm
		$d['akurasi'] = ($allTP[0]['SUM(TP)'] / $jmlhDU) * 100;

		foreach ($datamatrix as $k) {
			if ($k['TP'] == NULL){
				$k['TP'] = 0;  
			} else if($k['FP']== NULL){
				$k['FP']=0;
			} else if ($k['FN']== NULL){
				$k['FN']=0;
			}

			$cekp = $k['TP']+ $k['FP']; // cek pembagi, kalok 0 = tak hingga
			$cekr = $k['TP']+ $k['FN'];
			if ($cekp ==0 ){
				$presisikelas = 0;
			} else {
				$presisikelas = ($k['TP'] / $cekp);	
			}
			if ($cekr ==0 ){
				$recallkelas = 0;
			} else {
				$recallkelas = ($k['TP'] / $cekr);	
			}
			$this->m_klasifikasi->m_updateprerec($presisikelas, $recallkelas, $k['no_kelasddc']);
		}
		//presisi
		$pre = $this->m_klasifikasi->m_sumpresisi();
		$d['presisi'] = ($pre[0]['SUM(presisi)'] / $jmlhddc) * 100;
		//recall
		$rec = $this->m_klasifikasi->m_sumrecall();
		$d['recall'] = ($rec [0]['SUM(recall)']/ $jmlhddc)* 100;//recall
		return $d;
		// $this->load->view('v_akurasi',$d);
	}

	public function c_logout(){
		$this->session->sess_destroy();
		redirect('/c_klasifikasi/c_loginAdmin');
	}
}