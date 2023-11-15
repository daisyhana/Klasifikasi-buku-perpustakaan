<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class m_klasifikasi extends CI_Model{
	function __construct(){
		parent::__construct();
	}
	public function m_getdatatest(){
		$this->db->select('*');
		$this->db->from('data_latih');
		return $this->db->get()->result_array();
	}

	public function m_inputdatalatih($datalatih){
		$simpan = $this->db->insert("data_latih", $datalatih);
		$id = $this->db->insert_id();
		return $id;
	}

	public function m_inputDUmurni($kelas, $judul_buku,$pengarang,$penerbit,$time){
		$data = array(
			'no_kelasddc' => $kelas,
			'judul_buku' => $judul_buku,
			'pengarang' => $pengarang,
			'penerbit' => $penerbit,
			'waktu_eksekusi' => $time
		);
		$this->db->insert('nbc_murni', $data);
	} 

	public function m_inputDUgab($kelas, $judul_buku,$pengarang,$penerbit,$time){
		$data = array(
			'no_kelasddc' => $kelas,
			'judul_buku' => $judul_buku,
			'pengarang' => $pengarang,
			'penerbit' => $penerbit,
			'waktu_eksekusi' => $time
		);
		$this->db->insert('nbc_gabungan', $data);
	}

	public function m_getDataLatih(){
		return $this->db->get_where('data_latih',array('no_kelasddc !=' => NULL))->result_array();
	}

	public function m_countdatalatih(){
		$this->db->select('COUNT(*) AS dl');
		$this->db->from('data_latih');
		$this->db->where('level','latih');
		return $this->db->get()->result();
	}

	public function m_countdatauji(){
		$this->db->select('COUNT(*) AS du');
		$this->db->from('data_latih');
		$this->db->where('level','uji');
		return $this->db->get()->result();
	}

	public function m_countC1(){
		$this->db->select('COUNT(*) as c1');
		$this->db->from('preprocessing');
		$this->db->where('cluster','C1');
		return $this->db->get()->result();
	}

	public function m_countC2(){
		$this->db->select('COUNT(*) as c2');
		$this->db->from('preprocessing');
		$this->db->where('cluster','C2');
		return $this->db->get()->result();
	}

	public function m_countC3(){
		$this->db->select('COUNT(*) as c3');
		$this->db->from('preprocessing');
		$this->db->where('cluster','C3');
		return $this->db->get()->result();
	}

	public function m_updatekelasDU($id,$kelas){
		$sql = "UPDATE data_latih SET no_kelasddc = '$kelas' WHERE id_datalatih = $id ";
		$this->db->query($sql);
	}

	public function  m_getbukuDDC($noDDC){
		$this->db->select('*');
		$this->db->from('data_latih');
		$this->db->where('no_kelasddc',$noDDC);
		return $this->db->get()->result();
	}

	public function m_getkelasDDC($noDDC){
		$this->db->select('*');
		$this->db->from('data_kelasddc');
		$this->db->where('nomor_kelas',$noDDC);
		return $this->db->get()->result();
	} 

	public function m_getMedoid(){
		$this->db->select('*');
		$this->db->from('daftar_cluster');
		return $this->db->get()->result();
	}

	public function m_getBukunBobot(){
		$this->db->select('a.no_kelasddc,a.judul_buku,b.bobot_dokumen');
		$this->db->from('data_latih a');
		$this->db->join('preprocessing b','b.id_datalatih = a.id_datalatih');
		$this->db->order_by('b.cluster','asc');
		return $this->db->get()->result();
	}

	public function m_getNilaiCluster(){
		$this->db->select('a.no_kelasddc,a.judul_buku,b.cluster');
		$this->db->from('data_latih a');
		$this->db->join('preprocessing b','b.id_datalatih = a.id_datalatih');
		$this->db->order_by('b.cluster','asc');
		return $this->db->get()->result();
	}

	public function m_delmedoid(){
		$sql = "TRUNCATE TABLE data_medoid";
		$this->db->query($sql);
	}

	public function m_insertmedoid($id_cluster,$id_datalatih,$kelas){
		if($id_cluster==1){
			$keterangan = 'C1';
		}else if($id_cluster==2){
			$keterangan='C2';
		}else{
			$keterangan = 'C3';
		}
		$data = array(
			'id_cluster' => $id_cluster,
			'id_datalatih' =>$id_datalatih,
			'no_kelasddc' =>$kelas,
			'keterangan' =>$keterangan
		);
		$this->db->insert('data_medoid',$data);
	}

	public function m_getwakilMedoid(){
		$this->db->select('*');
		$this->db->from('data_medoid a');
		$this->db->join('data_latih b','b.id_datalatih = a.id_datalatih');
		return $this->db->get()->result();
	}

	public function m_preprocessing($prepro){
		$data = array(
			'id_datalatih' => $prepro['id_datalatih'],
			'case_folding' => $prepro['casefolding'],
			'tokenizing' => $prepro['tokenstring'],
			'filtering' => $prepro['filtering'],
			'stemming' => $prepro ['stemming'],
		);
		$this->db->insert('preprocessing',$data);
		$id= $this->db->insert_id();
		return $id;
	}

	public function m_test($id){
		$this->db->select('stemming');
		$this->db->from('preprocessing');
		$this->db->where('id_datalatih',$id);
		return $this->db->get()->result_array();
	}

	public function m_getpreprocessing(){
		$this->db->select('*');
		$this->db->from('preprocessing');
		$this->db->order_by('id_preprocessing','asc');
		return $this->db->get()->result_array();
	}

	public function m_deltbindex(){
		$sql = " TRUNCATE TABLE tbindex ";
		$this->db->query($sql);
	}

	public function m_counttbindex($dataprepro2,$docid){
		$this->db->select('Count');
		$this->db->from('tbindex');
		$this->db->where('Term',$dataprepro2);
		$this->db->where('DocId',$docid);
		return $this->db->get()->result_array();
	}

	public function m_updatetbindex($count,$term,$docid){
		$sql = "UPDATE tbindex SET Count = $count WHERE Term = '$term' AND DocId = $docid";
		$this->db->query($sql);
	}

	public function m_inserttbindex($term,$docid,$count){
		$sql = "INSERT INTO tbindex (Term,DocId,Count) VALUES('$term',$docid,$count)";
		$this->db->query($sql);
	}

	public function m_gettbindex(){
		$this->db->select('*');
		$this->db->from('tbindex');
		// $this->db->order_by('Id');
		return $this->db->get()->result_array();
	}

	public function m_gettfidf($id){
		$this->db->select('*');
		$this->db->from('tbindex');
		$this->db->where('DocId', $id);
		return $this->db->get()->result();
	}

	public function m_getbobotdoc($id){
		$this->db->select('bobot_dokumen');
		$this->db->from('preprocessing');
		$this->db->where('id_datalatih',$id);
		return $this->db->get()->result();
	}

	public function m_countalldoc(){
		return $this->db->get('data_latih')->num_rows();
	}

	public function m_getalldoc(){ // untuk hitung bobot dokumen
		$this->db->DISTINCT();
		$this->db->select('DocId');
		$this->db->from('tbindex');
		return $this->db->get()->result_array();
	}

	public function m_countNTerm($term){ // untuk hitung W
		$this->db->select('COUNT(*) AS N');
		$this->db->from('tbindex');
		$this->db->where('Term',$term);
		return $this->db->get()->result_array();
	}

	public function m_getTermbyID($id){
		$this->db->select('Term');
		$this->db->from('tbindex');
		$this->db->where('DocId',$id);
		return $this->db->get()->result_array();
	}

	public function m_updateTermWeight($id,$w){ // update bobot term
		$sql = " UPDATE tbindex SET Bobot = $w WHERE Id=$id ";
		$this->db->query($sql);
	}

	public function m_getTermWeight($docid){ //untuk hitung bobot dokumen
		$this->db->select('Bobot');
		$this->db->from('tbindex');
		$this->db->where('DocId',$docid);
		return $this->db->get()->result_array();
	}

	public function m_getTermWeightbyID($id){ //untuk hitung bobot dokumen
		$this->db->select('*');
		$this->db->from('tbindex');
		$this->db->where('DocId',$id);
		return $this->db->get()->result();
	}

	public function m_getAllTerm($kelas){ // untuk klasifikasi
		$this->db->select('Term');
		$this->db->from('tbindex a');
		$this->db->join('data_latih b','b.id_datalatih = a.DocId');
		$this->db->where('b.no_kelasddc',$kelas);
		return $this->db->get()->result_array();
	}

	public function m_updateDocWeight($docId,$amount){
		$sql = " UPDATE preprocessing SET bobot_dokumen = $amount WHERE id_datalatih=$docId";
		$this->db->query($sql);
	}

	public function m_getDocWeight(){
		$this->db->select('*');
		$this->db->from('preprocessing');
		return $this->db->get()->result_array();
	}

	public function m_getDocWeightbyID($id){
		$this->db->select('bobot_dokumen');
		$this->db->from('preprocessing');
		$this->db->where('id_datalatih',$id);
		return $this->db->get()->result();
	}

	public function m_getRandomData(){ // untuk clustering k-medoid
		$this->db->select('id_datalatih ,bobot_dokumen');
		$this->db->from('preprocessing');
		$this->db->order_by('rand()');
		$this->db->limit(1);
		return $this->db->get()->result_array();
	}

	public function m_getRandomc1(){ // untuk clustering k-medoid c1
		$this->db->select('id_datalatih , bobot_dokumen');
		$this->db->from('preprocessing');
		$this->db->order_by('rand()');
		$this->db->limit(1);
		$this->db->where('cluster','C1');
		return $this->db->get()->result_array();
	}

	public function m_getRandomc2(){ // untuk clustering k-medoid c2
		$this->db->select('id_datalatih , bobot_dokumen');
		$this->db->from('preprocessing');
		$this->db->order_by('rand()');
		$this->db->limit(1);
		$this->db->where('cluster','C2');
		return $this->db->get()->result_array();
	}

	public function m_getRandomc3(){ // untuk clustering k-medoid c3
		$this->db->select('id_datalatih , bobot_dokumen');
		$this->db->from('preprocessing');
		$this->db->order_by('rand()');
		$this->db->limit(1);
		$this->db->where('cluster','C3');
		return $this->db->get()->result_array();
	}

	public function m_getData($x){ // untuk klasifikasi, cari data yang punya bobot hasil klasifikasi
		$this->db->select('bobot_dokumen');
		$this->db->from('preprocessing');
		$this->db->where('id_preprocessing',$x);
		return $this->db->get()->result_array();
	}

	public function m_updateClusterData($id_datalatih,$cluster){
		$sql = " UPDATE preprocessing SET cluster = '$cluster' WHERE id_datalatih = $id_datalatih";
		$this->db->query($sql);
	}

	public function m_getcluster(){
		$this->db->select('*');
		$this->db->from('preprocessing');
		return $this->db->get()->result();
	}

	public function m_getClusterMember($cluster){ // ini belum dipake.
		$this->db->select_avg('bobot_dokumen');
		$this->db->from('preprocessing');
		$this->db->where('cluster','$cluster');
		return $this->db->get()->result_array();
	}

	public function m_delpeluangddc(){
		$sql = " TRUNCATE TABLE peluang_ddc ";
		$this->db->query($sql);
	}

	public function m_countpeluangddc($kelasddc){
		$this->db->select('count');
		$this->db->from('peluang_ddc');
		$this->db->where('no_kelas',$kelasddc);
		return $this->db->get()->result_array();
	}

	public function m_updatepeluangddc($count, $kelasddc){
		$sql = "UPDATE peluang_ddc SET count = $count WHERE no_kelas = '$kelasddc' ";
		$this->db->query($sql);
	}

	public function m_updatePDDCMedoids($count,$kelasddc){
		$sql = "UPDATE data_medoid SET count = $count WHERE no_kelasddc = '$kelasddc' ";
		$this->db->query($sql);
	}

	public function m_insertpeluangddc($kelasddc, $count){
		$sql = "INSERT INTO peluang_ddc (no_kelas,Count) VALUES('$kelasddc',$count)";
		$this->db->query($sql);
	}

	public function m_getDataMedoid(){
		$this->db->select('a.stemming, a.cluster');
		$this->db->from('preprocessing a');
		$this->db->join('data_medoid b', 'b.id_datalatih= a.id_datalatih');
		$this->db->order_by('b.keterangan');
		return $this->db->get()->result_array();
	}

	public function m_getketCluster(){
		$this->db->select('id_cluster,keterangan,id_datalatih,no_kelasddc,MAX(probabilitas)AS probabilitas');
		$this->db->from('data_medoid');
		return $this->db->get()->result_array();
	}

	public function m_getallkelas(){
		$this->db->select('*');
		$this->db->from('peluang_ddc');
		$this->db->order_by('Id');
		return $this->db->get()->result_array();
	}

	public function m_getkelasbymedoid(){
		$this->db->select('*');
		$this->db->from('data_medoid');
		$this->db->order_by('id_cluster');
		return $this->db->get()->result_array();
	}

	public function m_getDatabyCluster($cluster){
		$this->db->DISTINCT();
		$this->db->select('a.id,a.no_kelas,a.count,a.probabilitas,a.probkelasDU');
		$this->db->from('peluang_ddc a');
		$this->db->join('data_latih b', 'a.no_kelas = b.no_kelasddc');
		$this->db->join('preprocessing c','b.id_datalatih=c.id_datalatih');
		$this->db->where('c.cluster',$cluster);
		$this->db->order_by('a.id');
		return $this->db->get()->result_array(); 
	}

	public function m_getkelasWM($id){
		$this->db->select('no_kelasddc');
		$this->db->where('id_datalatih',$id);
		$this->db->from('data_latih');
		return $this->db->get()->result_array();
	}

	public function m_updateKelas($id,$prob){
		$sql = "UPDATE peluang_ddc SET probabilitas = $prob WHERE id = $id ";
		$this->db->query($sql);
	}

	public function m_updatekelasmedoids($kelas,$prob){
		$sql = "UPDATE data_medoid SET probabilitas = $prob WHERE no_kelasddc = '$kelas' ";
		$this->db->query($sql);
	}

	public function m_jmlhTermKelas($kelas){
		$this->db->select('SUM(Count)');
		$this->db->from('tbindex a');
		$this->db->join('data_latih b','b.id_datalatih = a.DocId');
		$this->db->where('b.no_kelasddc',$kelas);
		$query = $this->db->get();
		if($query->num_rows() != 0){
			return $query->result_array();
		} else { return false; }
	}

	public function m_updatePkelasDU($nilai, $kelas){
		$sql = "UPDATE peluang_ddc SET probkelasDU = $nilai WHERE no_kelas = '$kelas'";
		$this->db->query($sql);
	}

	public function m_updatePkelasDUM($nilai,$kelas){
		$sql = "UPDATE data_medoid SET probkelasDU = $nilai WHERE no_kelasddc = '$kelas'";
		$this->db->query($sql);
	}

	public function m_getPkelasDU(){
		$this->db->select_max('probkelasDU');
		$this->db->from('peluang_ddc');
		return $this->db->get()->result_array();
	}

	public function m_cariKelasDU($nilai){
		$this->db->select('no_kelas');
		$this->db->where('probkelasDU', $nilai);
		$this->db->from('peluang_ddc');
		return $this->db->get()->result_array();
	}

	public function m_getPkelasDUM(){
		$this->db->select_max('probkelasDU');
		$this->db->from('data_medoid');
		return $this->db->get()->result_array();
	}

	public function m_cariKelasDUM($nilai){
		$this->db->select('no_kelasddc');
		$this->db->where('probkelasDU', $nilai);
		$this->db->from('data_medoid');
		return $this->db->get()->result_array();
	}

	public function m_getdataMaster(){
		$this->db->select('*');
		$this->db->from('data_master');
		return $this->db->get()->result();
	}

	public function m_datamaster($nomor){
		if ($nomor==1 || $nomor == null){
			return $this->db->get('data_latih')->num_rows();	
		} else if($nomor==2){
			return $this->db->get('data_kelasddc')->num_rows();
		} else if($nomor==3){
			return $this->db->get('data_katadasar')->num_rows();
		} else {
			return $this->db->get('data_stopword')->num_rows();
		}
		
	}

	public function pagination_datamaster($number,$offset,$nomor){
		if ($nomor==1 || $nomor == null){
			return $this->db->get('data_latih',$number,$offset)->result();	
		} else if($nomor==2){
			return $this->db->get('data_kelasddc',$number,$offset)->result();
		} else if($nomor==3){
			return $this->db->get('data_katadasar',$number,$offset)->result();
		} else {
			return $this->db->get('data_stopword',$number,$offset)->result();
		}	
	}

	public function m_databukuUser(){
		return $this->db->get('data_latih')->num_rows();
	}

	public function pagination_buku($number, $offset,$judul=null){
		$this->db->select('*');
		if(!empty($judul)){
			$this->db->like('judul_buku',$judul);
		}
		return $this->db->get('data_latih',$number,$offset)->result();
	}

	// confusion matrix
	public function m_delconfusion(){
		$sql = "TRUNCATE TABLE confusion_matrix";
		$this->db->query($sql);
	} 

	public function m_getkelasprediksi(){
		$this->db->DISTINCT();
		$this->db->select('no_kelasddc');
		$this->db->from('data_latih');
		$this->db->where('level', 'uji');
		return $this->db->get()->result_array();
	}

	public function m_getpreNBCmurni(){
		$this->db->DISTINCT();
		$this->db->select('no_kelasddc');
		$this->db->from('nbc_murni');
		return $this->db->get()->result_array();
	}

	public function m_gettimeNBCmurni(){
		$this->db->select('SUM(waktu_eksekusi)');
		$this->db->from('nbc_murni');
		return $this->db->get()->result_array();
	}

	public function m_getpreNBCgab(){
		$this->db->DISTINCT();
		$this->db->select('no_kelasddc');
		$this->db->from('nbc_gabungan');
		return $this->db->get()->result_array();	
	}

	public function m_gettimeNBCgab(){
		$this->db->select('SUM(waktu_eksekusi)');
		$this->db->from('nbc_gabungan');
		return $this->db->get()->result_array();
	}

	public function m_ddcconfusion($kelas,$jmlh){
			$sql = "INSERT INTO confusion_matrix (no_kelasddc) VALUES('$kelas')";
			$this->db->query($sql);
	}

	public function m_getdataLU(){
		$this->db->select('*');
		$this->db->from('data_latih');
		$this->db->where('level','uji');
		return $this->db->get()->result_array();
	}

	public function m_getdataNBCmurni(){
		$this->db->select('*');
		$this->db->from('nbc_murni');
		return $this->db->get()->result_array();
	}

	public function m_getdataNBCgab(){
		$this->db->select('*');
		$this->db->from('nbc_gabungan');
		return $this->db->get()->result_array();
	}

	public function m_getdataaktual(){
		$this->db->select('*');
		$this->db->from('data_aktual');
		return $this->db->get()->result_array();
	}

	public function m_getTP($kelasddc){
		$this->db->select('TP');
		$this->db->from('confusion_matrix');
		$this->db->where('no_kelasddc',$kelasddc);
		return $this->db->get()->result_array();
	}

	public function m_updateTP($kelasddc,$tp){
		$sql = "UPDATE confusion_matrix SET TP = $tp WHERE no_kelasddc = '$kelasddc'";
		$this->db->query($sql);
	}

	public function m_getFN($kelasddc){
		$this->db->select('FN');
		$this->db->from('confusion_matrix');
		$this->db->where('no_kelasddc',$kelasddc);
		return $this->db->get()->result_array();
	}

	public function m_updateFN($kelasddc,$fn){
		$sql = "UPDATE confusion_matrix SET FN = $fn WHERE no_kelasddc = '$kelasddc'";
		$this->db->query($sql);
	}

	public function m_getFP($kelasddc){
		$this->db->select('FP');
		$this->db->from('confusion_matrix');
		$this->db->where('no_kelasddc',$kelasddc);
		return $this->db->get()->result_array();
	}

	public function m_updateFP($kelasddc,$fP){
		$sql = "UPDATE confusion_matrix SET FP = $fP WHERE no_kelasddc = '$kelasddc'";
		$this->db->query($sql);
	}

	public function m_inputaktnotexist($kelas,$tp, $fn, $fp){
		$sql = "INSERT INTO confusion_matrix(no_kelasddc,TP,FP,FN) SELECT no_kelasddc,TP,FP,FN FROM (SELECT '$kelas' as no_kelasddc,'$tp' as TP,'$fp' as FP,'$fn' as FN) as tmp WHERE NOT EXISTS (SELECT no_kelasddc FROM confusion_matrix WHERE no_kelasddc='$kelas') limit 1";
		$this->db->query($sql);
	}

	public function m_getconfusionmatrix(){
		$this->db->select('*');
		$this->db->from('confusion_matrix');
		return $this->db->get()->result_array();
	}

	public function m_sumTP(){
		$this->db->select('SUM(TP)');
		$this->db->from('confusion_matrix');
		return $this->db->get()->result_array();
	}

	public function m_updateprerec($presisi, $recall, $kelas){
		$sql = "UPDATE confusion_matrix SET presisi = $presisi, recall=$recall  WHERE no_kelasddc = '$kelas'";
		$this->db->query($sql);
	}

	public function m_sumpresisi(){
		$this->db->select('SUM(presisi)');
		$this->db->from('confusion_matrix');
		return $this->db->get()->result_array();
	}

	public function m_sumrecall(){
		$this->db->select('SUM(recall)');
		$this->db->from('confusion_matrix');
		return $this->db->get()->result_array();
	}

	//cek data input available atau tidak
	public function m_cekdatainput($judul_buku){
		$this->db->select('*');
		$this->db->from('data_latih');
		$this->db->where('judul_buku',$judul_buku);
		$cek = $this->db->get()->num_rows();
		if ($cek == 0){ // data tidak available, berarti nggak muncul pop-up
			$hasil = 0;
		} else {
			$hasil = 1;
		}
		return $hasil;
	}

	 public function m_getdataPopUp($judul_buku){
	 	$this->db->select('*');
	 	$this->db->from('data_latih');
	 	$this->db->where('judul_buku',$judul_buku);
	 	return $this->db->get()->result_array();
	 }
}