<?php 
require_once 'lib/PHPExcel.php';
//////////////////////////////////////////////////////////
///////// DATOS DE CONEXION A LA BASE DE DATOS //////////
//////////////////////////////////////////////////////////
$username = "root"; 
$password = "";
$hostname = "localhost";

$dbhandle = mysql_connect($hostname,$username,$password)
   or die("No es posible conectar a MySql");

$seleccion = mysql_select_db("wpcurso1030")//nombre de la base de datos
  or die("Base de datos no disponible");
//////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////
$sql = mysql_query("SELECT *
                    FROM wp_signals_historical t1
                    INNER JOIN wp_signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price_h)
                    ORDER BY date_h");

  while($row = mysql_fetch_array($sql)){
        if($row){
            echo "<pre>";
            print_r($row);
            echo "</pre>";
        }
    }
 

    exit();
//global $wpdb;
//
//$data = $wpdb->get_results( 
//                "SELECT *
//                    FROM ".$wpdb->prefix ."signals_historical t1
//                    INNER JOIN ".$wpdb->prefix ."signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price_h)
//                    ORDER BY date_h"); 
//
//        echo "<pre>";
//print_r($data);
//echo "</pre>";
//exit();