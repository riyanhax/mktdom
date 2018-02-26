<?php 
$numero=1;
add_action( 'admin_init', 'signals_styles' ); // cargar la hoja de estilos al entrar al plugin
add_action( 'wp_enqueue_scripts', 'signals_styles' ); 

/* Para que muestre los iconos incluso cuando no se esta logeado */
add_action( 'wp_enqueue_scripts', 'my_dashicons' );
function my_dashicons() {
	wp_enqueue_style( 'dashicons' );
}
/***************************************/

//definimos todas las hojas de estilos,js que vamos a utilizar en el plugin
function signals_styles() {
	wp_register_style( 'signals', plugins_url( 'signals/css/plugin.css' ) );
	wp_enqueue_style( 'signals' );
        wp_enqueue_style('dataTables','//cdn.datatables.net/1.10.13/css/jquery.dataTables.min.css');
        
        wp_enqueue_script('dataTables', '//cdn.datatables.net/1.10.13/js/jquery.dataTables.min.js', array('jquery'));
        wp_enqueue_script('function', plugins_url('js/function.js', __FILE__), array('jquery'));
        
        wp_enqueue_style('bootstrap.cdn', '//cdn.jsdelivr.net/bootstrap/latest/css/bootstrap.css');
}

//funcion que genera consultas sql de wordpress
function generaQueryReport($sql){
     global $wpdb;
     //fb($sql, '<br>--$sql-->', FirePHP::INFO);
     $sql=$wpdb->get_results($sql);
     return $sql;
}

//funcion que va a mostrar todos los registros de la tabla wp_signal_price
function muestraMonedas(){
    global $wpdb;
    $monedas=array();
    $sql = "SELECT * FROM ".$wpdb->prefix ."signals_price";
    $value = generaQueryReport($sql);
    
    foreach ($value as $values) {
     if($values){ //@cs validamos para que no salga error cuando no existan datos 
           $monedas[] = $values;
         }
    }
    return $monedas;
    
    
    
}
//hace referencia a funciones invocadas desde ajax 
add_action('wp_ajax_registra', 'registraDatos');
add_action('wp_ajax_elimina', 'eliminaDatos');
add_action('wp_ajax_cancelar', 'cancelaDatos');
add_action('wp_ajax_actualiza', 'actualiza_TakeProfit_StopLoss');
add_action('wp_ajax_actualizar_comentario', 'actualizar_comentario');

add_action( 'wp_ajax_stop_loss', 'switch_ls' );
add_action( 'wp_ajax_take_profit', 'switch_tp' );
add_action( 'wp_ajax_switch_edit', 'switch_edit' );
add_action( 'wp_ajax_resetEdit', 'resetEdit' );
add_action( 'wp_ajax_capturar_publicidad', 'capturar_publicidad' );
//add_action('wp_ajax_consulta', 'consultaDatos'); //ajax no esta en marcha

//Funcion que dverifica si exiten datos para realizar el bucle
/*function consultaDatos(){
    global $wpdb;
    $count=0;
    if (isset($_POST['action'])) {
        $count = $wpdb->get_var( "SELECT count(*) FROM ".$wpdb->prefix."signals WHERE result = 0 AND cancel = 0" );
    }
    echo json_encode($count);
    exit();
}*/

function switch_ls() {
    $numero = intval( $_POST['whatever'] );
    global $wpdb;
    $table_signals = $wpdb->prefix . "signals";
    $data = $wpdb->get_results( 
                "SELECT *
                 FROM ".$wpdb->prefix ."signals t1
                 INNER JOIN ".$wpdb->prefix ."signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price)
                 ORDER BY ID desc"
    ); 
    if(count($data) > 0){ //validamos en caso de que no exista datos registrados en la BD
        foreach ( $data as $signal ){
            $wpdb->query('update '.$table_signals.' set switch_sl='.$numero);
        }
        
    }
    $cad = draw_table_signal();
    echo json_encode($cad);
    exit();
    wp_die();
}

function switch_tp() {
	//global $wpdb; // this is how you get access to the database
        //global $numero;
	$numero = intval( $_POST['whatever'] );

	//$numero += 10;

        //echo $numero;

	//wp_die(); // this is required to terminate immediately and return a proper response
    
        
    global $wpdb;
    $table_signals = $wpdb->prefix . "signals";
    $data = $wpdb->get_results( 
                "SELECT *
                 FROM ".$wpdb->prefix ."signals t1
                 INNER JOIN ".$wpdb->prefix ."signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price)
                 ORDER BY ID desc"
    ); 
    if(count($data) > 0){ //validamos en caso de que no exista datos registrados en la BD
        foreach ( $data as $signal ){
            //$wpdb->query("UPDATE ".$wpdb->prefix ."signals SET ".$signal->switch_sl."=".$numero);
            $wpdb->query('update '.$table_signals.' set switch_tp='.$numero);
            //$wpdb->update($table_signals, array('switch_sl'=>$numero), array('result' => 0));
        }
        
    }
    $cad = draw_table_signal();
    echo json_encode($cad);
    exit();
    wp_die();
}

function cancelaDatos(){
    global $wpdb;
    if (isset($_POST['action'])) {
        if($_POST['id_signal'] >0){
            $resultado=0;
            $id_signal=$_POST['id_signal'];
            $valor=$_POST['valor'];
            $cod_price=$_POST['cod_price'];
            $tipo_signal=$_POST['tipo_signal'];
            $signal=$_POST['signal'];
            $stop_loss=$_POST['stop_loss'];
            $take_profit=$_POST['take_profit'];
            $price_signal=$_POST['price_signal'];
            $orden_pendiente=$_POST['orden_pendiente'];
            $cod_op=$_POST['cod_op'];
        
            $precio_actual=generaPriceSignal($cod_price,$tipo_signal,$signal); //Obtiene el precio actual de la moneda
            
//            echo "<br> --- stop_loss ---->" . $stop_loss;
//            echo "<br> --- take_profit ---->" . $take_profit;
//            echo "<br> --- price_signal ---->" . $price_signal;
//            echo "<br> --- precio_actual ---->" . $precio_actual;
//            echo "<br> --- orden_pendiente ---->" . $orden_pendiente;
            /* Verifica cual es el mas cernano para colocar la señal */
            
            
            if($signal != 'Pending Order'){
            
                $resultado=calculaMayor($stop_loss,$price_signal,$precio_actual,'SL');
                
                if($resultado == 0){
                   $resultado=calculaMayor($take_profit,$price_signal,$precio_actual,'TP');
                }
            }else{
                if($cod_op > 0){ //validamos si ya toco at_price
                    
                    $resultado=calculaMayor($stop_loss,$orden_pendiente,$precio_actual,'SL');
                    
                    if($resultado == 0){
                       $resultado=calculaMayor($take_profit,$orden_pendiente,$precio_actual,'TP');
                    }
                    
                    $price_signal=$orden_pendiente;
                }else{
                    if($cod_op == 0){
                        $table_signals = $wpdb->prefix . "signals";
                        $wpdb->update($table_signals, array('cancel'=>1,'result'=>3), array('ID' => $id_signal));
                    }
                }
            }
            
            if($resultado > 0){
                    /*
                    if($resultado == 1){
                        $pips = ($stop_loss-$price_signal)*10000;
                    }else{
                        $pips = ($take_profit-$price_signal)*10000;
                    }
                    */

                if( $cod_price == 6 || $cod_price == 7 || $cod_price == 9 || $cod_price == 12){
                    $pips = ($precio_actual-$price_signal)*100;
                }            
                else{
                    $pips = ($precio_actual-$price_signal)*10000;
                }
                
                //$pips=abs(round($pips * 10)/10);
                $pips=abs(round($pips,2));
                
                $closing_price_g = $precio_actual;
                
                date_default_timezone_set("Europe/Berlin"); //muestra la fecha y hora de La Paz Bolivia
                $closing_time = date("H:i:s");
                
                $table_signals = $wpdb->prefix . "signals";
                $wpdb->update($table_signals, array('result' => $resultado,'pips' =>$pips,'closing_price' =>$closing_price_g,'cancel'=>1,'closing_time'=>$closing_time), array('ID' => $id_signal));        
            }
            resetEdit();
            $cad = draw_table_signal();
            echo json_encode($cad);
            exit();
            
            
            
        }
    }
}

function actualiza_TakeProfit_StopLoss(){
    global $wpdb;
    if (isset($_POST['action'])) {
        if($_POST['id_signal'] >0){
            $id_signal=$_POST['id_signal'];
            $stop_loss_edit=$_POST['stop_loss_edit'];
            $take_profit_edit=$_POST['take_profit_edit'];
            $valor=$_POST['valor'];
        
            $table_signals = $wpdb->prefix . "signals";
            $wpdb->update($table_signals, array('stop_loss_edit' =>$stop_loss_edit,'take_profit_edit' =>$take_profit_edit,'switch_edit' =>$valor), array('ID' => $id_signal));     
       
            $cad = draw_table_signal();
            echo json_encode($cad);
            exit();
        }
    }
}

function actualizar_comentario(){
    global $wpdb;
    if (isset($_POST['action'])) {
        if($_POST['id_signal'] >0){
            $id_signal=$_POST['id_signal'];
            $comentario=$_POST['whatever'];
        
            $table_signals = $wpdb->prefix . "signals";
            $wpdb->update($table_signals, array('commentary' =>$comentario), array('ID' => $id_signal));     
       
            $cad = draw_table_signal();
            echo json_encode($cad);
            exit();
        }
    }
}

function capturar_publicidad(){
    global $wpdb;
    if (isset($_POST['action'])) {
        if($_POST['id_signal'] >0){
            $id_signal=$_POST['id_signal'];
        
            $table_signals = $wpdb->prefix . "signals";
            $ultimo_registro = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix ."signals WHERE ID=".$id_signal );     
       
            $cad = draw_table_signal();
            //echo json_encode($cad);
            echo $ultimo_registro->commentary;
            exit();
        }
    }
}

function switch_edit(){
    global $wpdb;
    if (isset($_POST['action'])) {
        if($_POST['id_signal'] >0){
            $id_signal=$_POST['id_signal'];
            $sw_edit=$_POST['whatever'];
        
            $table_signals = $wpdb->prefix . "signals";
            
            resetEdit();
            
            $wpdb->update($table_signals, array('switch_edit' =>$sw_edit), array('ID' => $id_signal));        
       
            $cad = draw_table_signal();
            echo json_encode($cad);
            exit();
            wp_die();
        }
    }    
}

function resetEdit(){
    global $wpdb;
    $table_signals = $wpdb->prefix . "signals";
            
    $data = $wpdb->get_results( 
        "SELECT *
         FROM ".$wpdb->prefix ."signals t1
         INNER JOIN ".$wpdb->prefix ."signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price)
         ORDER BY ID desc"
    ); 
    if(count($data) > 0){ //validamos en caso de que no exista datos registrados en la BD
        foreach ( $data as $signal ){
            $wpdb->query('update '.$table_signals.' set switch_edit=0');
        }
    }
}

function calculaMayor($valor1,$valor2,$precio_actual,$sl_tp){
    $data=array();
    $resultado=0;
        if($valor1>$valor2){
            $data['mayor']=$valor1;
            $data['menor']=$valor2;
        }else{
            $data['mayor']=$valor2;
            $data['menor']=$valor1;
        }
        if($precio_actual <= $data['mayor'] && $precio_actual >= $data['menor']){
               if($sl_tp == 'TP'){
                   $resultado=2; 
               }else{
                   $resultado=1;
               }
        }
        
        return $resultado;
        
}

function cancelaDatos1(){
    global $wpdb;
    $table_name = $wpdb->prefix . "signals";
    if (isset($_POST['action'])) {
        if($_POST['id_signal'] >0)
        $wpdb->update($table_name, array('cancel' => $_POST['valor']), array('ID' => $_POST['id_signal']));
    }
    $cad = draw_table_signal();
    echo json_encode($cad);
    exit();
}

function registraDatos(){
    
    global $wpdb;
    $table_name = $wpdb->prefix . "signals";
    $table_name_historico = $wpdb->prefix . "signals_historical";
    
    
//    date_default_timezone_set("America/New_York"); //muestra la fecha y hora de New YORK
    date_default_timezone_set("Europe/Berlin"); //muestra la fecha y hora de La Paz Bolivia
    $hora_new_york = date("H:i:s");
    $fecha_new_york = date("Y")."-".date("m")."-".date("d"); //el formtao de la fecha es 2016-12-18
    
    $cod_asset = $_POST['cod_asset'];
    $cod_calidad = $_POST['cod_calidad'];
    $stop_loss = $_POST['stop_loss'];
    $take_profit = $_POST['take_profit'];
    $tipo_signal = $_POST['tipo_signal'];
    $signal = $_POST['signal'];
    $orden_pendiente = $_POST['orden_pendiente'];
    $method_e = $_POST['method_e'];
    $method_link_e = $_POST['method_link_e'];
    $rr_link_g = $_POST['rr_link_g'];
    $publi_comentario_g = $_POST['publi_comentario'];
    actualizaPrecios(); //actualizar precios

    $precio_signal=generaPriceSignal($cod_asset,$tipo_signal,$signal); //funcion que va a obtner el precio con el que esta comprando o vendiendo
    
   
     /******************/
    if($signal == 'Pending Order'){
         $orden_pendiente=conviertePIP($cod_asset,$tipo_signal,$signal,$precio_signal,'PO',$orden_pendiente);
         $stop_loss= conviertePIP($cod_asset,$tipo_signal,$signal,$orden_pendiente,'SL',$stop_loss);
         $take_profit= conviertePIP($cod_asset,$tipo_signal,$signal,$orden_pendiente,'TP',$take_profit);
     }else{
         /*Funcion que convierte a PIPs*/
         $stop_loss= conviertePIP($cod_asset,$tipo_signal,$signal,$precio_signal,'SL',$stop_loss);
         $take_profit= conviertePIP($cod_asset,$tipo_signal,$signal,$precio_signal,'TP',$take_profit);
     }
    
    
    /*Fucnion que convierte a PIPs*/
//     $stop_loss= conviertePIP($tipo_signal,$signal,$precio_signal,'SL',$stop_loss);
//     $take_profit= conviertePIP($tipo_signal,$signal,$precio_signal,'TP',$take_profit);
     
    /* if($signal === 'Pending Order'){
         $orden_pendiente=conviertePIP($tipo_signal,$signal,$precio_signal,'PO',$orden_pendiente);
     }*/
         
    $resultado = calculaResultado(null,$cod_asset, $stop_loss, $take_profit, $signal,$tipo_signal,$orden_pendiente);
       $data=array(
            'date' => $fecha_new_york,
            'time' => $hora_new_york,
            'address' => $signal,
            'type_of_order' => $tipo_signal,
            'cod_entry_price' => $cod_asset,
            'stop_loss' => $stop_loss,
            'take_profit' => $take_profit,
            'quality' => $cod_calidad,
            'orden_pendiente' => $orden_pendiente,
            'result' => $resultado,
            'cancel' => 0,
            'price_signal' => $precio_signal,
            'method' => $method_e,
            'method_link' => $method_link_e,
            'rr_link' => $rr_link_g,
            'commentary' => $publi_comentario_g,
        );
    /* Validamos para que solo existan 20 FIFO */   
    $total = $wpdb->get_var( "SELECT COUNT(*) FROM wp_signals" );//consultamos cuantos registros existe en la BD
    if($total > 0){
    
        if($total < 40){ //numero maximo para almacenar la tabla actualmene estaba 20 pero con los ultimos cambios se modifico a 40
           $wpdb->insert($table_name,$data); // realiza el insert
//        }else{//en caso de que los registros sean mas de 20 eliminamos el registro mas antiguo 
        }else{//en caso de que los registros sean mas de 20 guardamos en otra tabla historica
//            $id_ultimo = $wpdb->get_var( "SELECT ID FROM wp_signals ORDER BY date" );
            $ultimo_registro = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix ."signals ORDER BY ID" );
            if ($ultimo_registro->ID > 0) {
//                $wpdb->delete( $table_name, array( 'ID' => $id_ultimo ) );
                $data_historico = array(
                    'date_h' => $ultimo_registro->date,
                    'time_h' => $ultimo_registro->time,
                    'address_h' => $ultimo_registro->address,
                    'type_of_order_h' => $ultimo_registro->type_of_order,
                    'cod_entry_price_h' => $ultimo_registro->cod_entry_price,
                    'stop_loss_h' => $ultimo_registro->stop_loss,
                    'take_profit_h' => $ultimo_registro->take_profit,
                    'orden_pendiente_h' => $ultimo_registro->orden_pendiente,
                    'quality_h' => $ultimo_registro->quality,
                    'result_h' => $ultimo_registro->result,
                    'price_signal_h' => $ultimo_registro->price_signal,
                    'pips_h' => $ultimo_registro->pips,
                );
                $wpdb->insert($table_name_historico,$data_historico); //insertamos el registro antiguo a la tabla historica
                $wpdb->delete( $table_name, array( 'ID' => $ultimo_registro->ID ) ); //eliminanos de la tabla signals
            }
            $wpdb->insert($table_name,$data);
        }
    }else{
        $wpdb->insert($table_name,$data);
    }
    
    $cad = draw_table_signal();
    echo json_encode($cad);
    exit(); 
}

function generaPriceSignal($cod_asset,$tipo_signal,$signal){
    global $wpdb;
    $precio=0;
    $signals_price = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix ."signals_price"." WHERE cod_entry_price =".$cod_asset );
    
    switch ($tipo_signal) {
         case 'Spot':
                            if($signal == 'Sell'){ // preguntamos si la señal es Vender
                                $precio = $signals_price->price_sell;
                            }else{
                                if($signal == 'Buy'){ //Preguntamos si es Comprar
                                 $precio = $signals_price->price_buy;   
                                }    
                            }    
                break;
            
            case 'Buy Stop':    
            case 'Buy Limit':
                            $precio = $signals_price->price_buy;  
            break;

            case 'Sell Limit':
            case 'Sell Stop':
                            $precio = $signals_price->price_sell;
            break;

            default:
                break;
    }
    
    return $precio;
}

function eliminaDatos(){
    global $wpdb;
    $table_name = $wpdb->prefix . "signals";
    if (isset($_POST['action'])) {
        $wpdb->delete( $table_name, array( 'ID' => $_POST['id_signal'] ) );
    }
    $cad = draw_table_signal();
    echo json_encode($cad);
    exit();
} 

add_action('wp_ajax_refresh', 'refresh_table'); //muestra la tabla cuando el usuario esta logeado
add_action('wp_ajax_nopriv_refresh', 'refresh_table'); //muetra la tabla cuando el usuario no este logeado

/* VISTAS PARA EL HOME */
add_action('wp_ajax_refresh_home', 'refresh_table_home'); 
add_action('wp_ajax_nopriv_refresh_home', 'refresh_table_home'); 

/* VISTAS PARA EL HOME */
add_action('wp_ajax_refresh_membership', 'refresh_table_membership'); 
add_action('wp_ajax_nopriv_refresh_membership', 'refresh_table_membership'); 

add_action('wp_ajax_signal_settings', 'settings'); 

//Funcion que modifica el numero de registros para mostrar en la vista home y membership
function settings(){
    global $wpdb;
    if (isset($_POST['action'])) {
        $table_signals_settings = $wpdb->prefix . "signals_settings";
        
        $home = $_POST['home'];
        $membership = $_POST['membership'];
                
        $wpdb->update($table_signals_settings, array('nro_registros'=>$home), array('vista' => 'home'));
        $wpdb->update($table_signals_settings, array('nro_registros'=>$membership), array('vista' => 'membership'));
    }
    exit();
}

//VISTA PARA EL membership
function refresh_table_membership(){
    actualizaPrecios(); //actualizar precios
    $cad = draw_table_signal_membership(); //actualizar y mostrar tabla
    echo json_encode($cad);
    exit();
}

//VISTA PARA EL HOME
function refresh_table_home(){
    actualizaPrecios(); //actualizar precios
    $cad = draw_table_signal_home(); //actualizar y mostrar tabla
    echo json_encode($cad);
    exit();
}


//VISTA PARA EL ADMINSITRADOR
function refresh_table(){
    actualizaPrecios(); //actualizar precios
    $cad = draw_table_signal(); //actualizar y mostrar tabla
    echo json_encode($cad);
    exit();
}

//TABLA PARA EL ADMINSITRADOR
function draw_table_signal(){
    
updateTableSignals(); //funcion va actualizar la tabla para ver si existen cambios
global $wpdb;
$cont_pips_bien=0;
$cont_pips_mal=0;
$suma_rr_g=0;
$num_rr_g=0;
$prom_rr_g=0;
$num_g=0;
global $numero;

$data = $wpdb->get_results( 
                "SELECT *
                 FROM ".$wpdb->prefix ."signals t1
                 INNER JOIN ".$wpdb->prefix ."signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price)
                 ORDER BY ID desc"
); 

$ultimo=$wpdb->get_row('SELECT * FROM '.$wpdb->prefix .'signals ORDER BY ID desc LIMIT 1');
$class_quality='';
$cad = "";

    $cad .= '<div class="container-fluid">
	<div class="row">
	            <div class="col-md-12"> 
			<table class="table" id="table_result">
				<thead>
					<tr>
						<th class="text-center">
							'.$ultimo->switch_tp.'
						</th>
                                                <th class="text-center" style="display:none">
							ID 
						</th>
						<th class="text-center">
							DATE / TIME
						</th>
						<!--<th class="text-center">
							SIGNAL TIME
						</th>-->
						<!--<th class="text-center">
							DIRECTION
						</th>-->
						<th class="text-center">
							ORDER TYPE 
						</th>
						<th class="text-center">
							ASSET
						</th>
                                                <th class="text-center">
							METHOD
						</th>
						<th class="text-center">
							ENTRY PRICE
						</th>
                                                <th class="text-center" onclick="switch_stoploss();">
                                                    STOP LOSS';
                                                    if($ultimo->switch_sl==0){
                                                        $cad.='<div style="font-size: 8px;" id="sw_sl_g">(Original)</div>';
                                                    }else{
                                                        $cad.='<div style="font-size: 8px;" id="sw_sl_g">(Edited)</div>';
                                                    }
						$cad.='</th>
                                                <th class="text-center" onclick="switch_takeprofit()">
                                                    TAKE PROFIT';
                                                    if($ultimo->switch_tp==0){
                                                        $cad.='<div style="font-size: 8px;" id="sw_sl_g">(Original)</div>';
                                                    }else{
                                                        $cad.='<div style="font-size: 8px;" id="sw_sl_g">(Edited)</div>';
                                                    }
						$cad.='</th>
						<th class="text-center">
							R/R
						</th>
                                                <th class="text-center">
							CLOSING PRICE
						</th>
						<th class="text-center">
							CLOSING TIME
						</th>
						<!--<th class="text-center">
							RESULT
						</th>-->
						<th class="text-center">
							PIPS
						</th>';
				$cad .= '<th class="text-center">
					    ACTIONS
					</th>';
                                $cad .= '<th class="text-center">
					    EDIT COMENT
					</th>';
                                $cad .= '<th class="text-center">
					    EDIT TP_SL
					</th>';
				   $cad .= '</tr>
				</thead>';
    
			$cad .='<tbody>';
                        if(count($data) > 0){ //validamos en caso de que no exista datos registrados en la BD
                            foreach ( $data as $signal ){
                            $result='';
                            $style_pips='';
                            $style_ok_po='';
                            
                            $precio = $signal->price_signal;
                            $stop_loss = $signal->stop_loss;
                            $take_profit = $signal->take_profit;
                            $cod_op_g = $signal->cod_op;
                            $asset = $signal->asset;
                            $stop_loss_edit=$signal->stop_loss_edit;
                            $take_profit_edit=$signal->take_profit_edit;
                            
                            if($signal->address == 'Pending Order'){ //validamos para que entry price muestre orden pendiente cuando sea el caso
                                $precio=$signal->orden_pendiente;
                                
                                /*
                                if($signal->cod_op == 0 && $signal->cancel == 0){
                                    $stop_loss = "<span class='dashicons dashicons-minus'></span>";
                                    $take_profit = "<span class='dashicons dashicons-minus'></span>";
                                }
                                */

                                if($signal->cod_op == 1 && $signal->cancel == 0 && $signal->result == 0){
//                                    $style_ok_po="style='background-color:#00cc00;color:#fff;font-weight: bold;'";
                                    $style_ok_po="class='yes_green'";
                                }
                                
                            }
                            
                            switch ($signal->result) {
                                case 2:
                                        $result ="<span class='dashicons dashicons-yes color_verde'></span>"; 
                                        //$style_pips="class='yes_green'";
                                        $style_pips="style='color: #00cc00;font-weight: bold;'";
                                        $cont_pips_bien= $cont_pips_bien+$signal->pips; //cuenta los pips ganados
                                break;
                                case 1:
                                        $result ="<span class='dashicons dashicons-no color_rojo'></span>"; 
                                        //$style_pips="class='no_red'";
                                        $style_pips="style='color: red;font-weight: bold;'";
                                        $cont_pips_mal= $cont_pips_mal+$signal->pips; //cuenta los pips perdidos
                                break;

                                default:
                                         $result ="<span class='dashicons dashicons-minus color_am'></span>"; 
                                break;
                            }
                            
                            if($signal->closing_time){
                                $closing_time=$signal->closing_time;
                            }else{
                                $closing_time="<span class='dashicons dashicons-minus color_am'></span>";
                            }
                            
                            //validamos para cuando la calidad sea 4 o 5 se pinte de color verde
                            if($signal->quality == 4 || $signal->quality == 5){
                            $class_quality="class='yes_green'";
                            }else{
                                $class_quality='';
                            }
                            if($signal->address=="Pending Order"){
                                if(substr($signal->type_of_order,0,1)=="S"){
                                    $order_type_e="<span class='dashicons dashicons-arrow-down-alt color_rojo'></span>"." &nbsp;&nbsp; S ".substr($signal->type_of_order,strpos($signal->type_of_order, ' ')+1,1);
                                    if($signal->result==0){    
                                        if($cod_op_g==1){
                                            $closing_price_g = $signal->price_sell;
                                            if($closing_price_g<=$precio){
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$precio)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$precio)*10000;
                                                }
                                                $style_pips="style='color: #00cc00;font-weight: bold;'";
                                            }else{
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$precio)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$precio)*10000;
                                                }
                                                $style_pips="style='color: #ff0000;font-weight: bold;'";
                                            }      
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }else if($signal->result==3){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a>'.substr($signal->closing_price."", -3);
                                            $style_pips="style='font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }
                                else{
                                    $order_type_e="<span class='dashicons dashicons-arrow-up-alt color_verde'></span>"." &nbsp;&nbsp; B ".substr($signal->type_of_order,strpos($signal->type_of_order, ' ')+1,1);
                                    if($signal->result==0){   
                                        if($cod_op_g==1){
                                            $closing_price_g = $signal->price_buy;
                                            if($closing_price_g>=$signal->orden_pendiente){
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*10000;
                                                }
                                                $style_pips="style='color: #00cc00;font-weight: bold;'";
                                            }else{
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*10000;
                                                }
                                                $style_pips="style='color: #ff0000;font-weight: bold;'";
                                            }
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }else if($signal->result==3){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a>'.substr($signal->closing_price."", -3);
                                            $style_pips="style='font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }                            
                            }else{
                                if($signal->address=="Sell"){ 
                                    $order_type_e="<span class='dashicons dashicons-arrow-down-alt color_rojo'></span>"." &nbsp;&nbsp; ".$signal->type_of_order;
                                    if($signal->result==0){  
                                        $closing_price_g = $signal->price_sell;
                                        if($closing_price_g<=$precio){
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #00cc00;font-weight: bold;'";
                                        }else{
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }
                                else{
                                    $order_type_e="<span class='dashicons dashicons-arrow-up-alt color_verde'></span>"." &nbsp;&nbsp; ".$signal->type_of_order;
                                    if($signal->result==0){ 
                                        $closing_price_g = $signal->price_buy;
                                        if($closing_price_g>=$precio){
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #00cc00;font-weight: bold;'";
                                        }else{
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }
                            }
                            
                            if($signal->result>0){
                                $suma_rr_g+=$signal->take_profit/$signal->stop_loss;
                                $num_rr_g++;
                            }
                            $num_g++;
                            $pips_g=abs(round($pips_g,2));
                            /*if($signal->cancel==1){
                                $pips_g=$signal->pips;
                            }*/
                                        $cad.='<tr class="active">
                                                    <td>'.$num_g.'</td>
                                                   <td style="display:none">'.$signal->ID.'</td>
                                                   <td>'.$signal->date.' / '.$signal->time.'</td>
                                                    <!--<td>'.$signal->time.'</td>-->
                                                    <!--<td>'.convierteIniciales($signal->address,'direction').'</td>
                                                    <td>'.convierteIniciales($signal->type_of_order,'order_type').'</td>-->
                                                    <td>'.$order_type_e.'</td>
                                                    <td class="color_text">'.$signal->asset.'</td>
                                                    <td ><a target="_blank" href="'.$signal->method_link.'">'.$signal->method.'</a></td>
                                                    <td '.$style_ok_po.'>'.digitos($precio,$signal->cod_entry_price).'</td>';
                                                    
                                                    
                                        
                                                    if($signal->switch_sl==0){
                                                        if($stop_loss_edit==0){
                                                            $cad.='<td>'.$stop_loss.'</td>';
                                                        }else{
                                                            $cad.='<td style="color:#00ffff">'.$stop_loss.'</td>';
                                                        }
                                                    }else{
                                                        //$cad.='<td>'.conviertePIP($asset,$signal->type_of_order,$signal->address,$precio,'SL',$stop_loss_edit).'</td>';
                                                        //$stop_loss=conviertePIP($asset,$signal->type_of_order,$signal->address,$precio,'SL',$stop_loss_edit);
                                                        if($stop_loss_edit==0){
                                                            $cad.='<td>'.$stop_loss.'</td>';
                                                        }else{
                                                            $cad.='<td style="color:#00ffff">'.conviertePIP($asset,$signal->type_of_order,$signal->address,$precio,'SL',$stop_loss_edit).'</td>';
                                                        }
                                                    }
                                                    
                                                    if($signal->switch_tp==0){
                                                        if($take_profit_edit==0){
                                                            $cad.='<td>'.$take_profit.'</td>';
                                                        }else{
                                                            $cad.='<td style="color:#00ffff">'.$take_profit.'</td>';
                                                        }
                                                    }else{
                                                        //$cad.='<td>'.conviertePIP($asset,$signal->type_of_order,$signal->address,$precio,'SL',$take_profit_edit).'</td>';
                                                        //$stop_loss=conviertePIP($asset,$signal->type_of_order,$signal->address,$precio,'SL',$take_profit_edit);
                                                        if($take_profit_edit==0){
                                                            $cad.='<td>'.$take_profit.'</td>';
                                                        }else{
                                                            $cad.='<td style="color:#00ffff">'.conviertePIP($asset,$signal->type_of_order,$signal->address,$precio,'TP',$take_profit_edit).'</td>';
                                                        }
                                                    }
                                                    //**************  COLUMNA R / R  *******************
                                                    if($signal->address=="Pending Order" && $signal->cod_op=0){
                                                        $cad.='<td '.$class_quality.'></td>';
                                                    }else{
                                                        if($signal->switch_sl!=0 && $stop_loss_edit!=0){
                                                            $var_sl=conviertePIP($asset,$signal->type_of_order,$signal->address,$precio,'SL',$stop_loss_edit);
                                                        }else{
                                                            $var_sl=$stop_loss;
                                                        }
                                                        if($signal->switch_tp!=0 && $take_profit_edit!=0){
                                                            $var_tp=conviertePIP($asset,$signal->type_of_order,$signal->address,$precio,'TP',$take_profit_edit);
                                                        }else{
                                                            $var_tp=$take_profit;
                                                        }
                                                        $cad.='<td '.$class_quality.'>'.round($var_tp/$var_sl,5).'<a href="'.$signal->rr_link.'">(?)</a></td>';
                                                    }
                                                    
                                                    
                                                    //$cad.='<td '.$class_quality.'>'.$var_tp.'  '.$var_sl.'<a href="'.$signal->rr_link.'">(?)</a></td>';
                                                    
                                                    $cad.='<td>'.$closing_price_c.'</td>  
                                                    <td>'.$closing_time.'</td>
                                                    <!--<td>'.$result.'</td>-->
                                                    <td '.$style_pips.'>'.$signal->pips.' '.$pips_g.'</td>';
                                                    if($signal->result == 0){    
                                                        if($signal->cancel == 1){
                                                            $valor=0;
                                                            $cad .='<td><a href="javascript:void(0);" onclick="javascript:signal.cancel(\''.$signal->ID.'\',\''.$valor.'\')" title="habilitar"> <span class="dashicons dashicons-lock color_cancel"></span></a></td>';
                                                        }else{
                                                            $valor=1;
                                                            $cad .='<td><a href="javascript:void(0);" onclick="javascript:signal.cancel(\''.$signal->ID.'\',\''.$valor.'\',\''.$signal->cod_entry_price.'\',\''.$signal->type_of_order.'\',\''.$signal->address.'\',\''.$signal->stop_loss.'\',\''.$signal->take_profit.'\',\''.$signal->price_signal.'\',\''.$signal->orden_pendiente.'\',\''.$signal->cod_op.'\')" title="cancelar"> <span class="dashicons dashicons-unlock color_cancel"></span></a></td>';
                                                        }
                                                    }else{
                                                        $cad .='<td><span class="dashicons dashicons-unlock"></span></td>';
                                                    }
                                                    
                                                    
                                                    
                                                    if($signal->result == 0){
                                                        $cad .='<td><a href="javascript:void(0);" id="btn_editar_publi" title="Edit publication" onclick="editarPublicacion(\''.$signal->ID.'\',\''.$num_g.'\')"><span class="dashicons dashicons-welcome-write-blog" style="color:#00ffff"></span></a></td>';
                                                    }else{
                                                        $cad .='<td id="col_edit"><span class="dashicons dashicons-welcome-write-blog"></span></td>';
                                                    }
                                                    
                                                    
                                                    
                                                    
                                                    
                                                    if($signal->result == 0){
                                                        $cad .='<td><a href="javascript:void(0);" id="btn_editar" onclick="editarDatosTP_SL_edit(\''.$signal->ID.'\',\''.$signal->stop_loss.'\',\''.$signal->take_profit.'\',\''.$num_g.'\',\''.$precio.'\',\''.substr( $asset, -3).'\')" title="Edit signal"><span class="dashicons dashicons-edit" style="color:#00cc00"></span></a></td>';
                                                    }else{
                                                        $cad .='<td><span class="dashicons dashicons-edit"></span></td>';
                                                    }
                                                    
                                                    
                                                    $cad .='<td><a href="javascript:void(0);" id="btn_mostrar_publi" onclick="mostrarPublicidad(\''.$signal->ID.'\',\''.$num_g.'\')" title="Commentary"><span class="dashicons dashicons-admin-comments" style="color:#ff3"></span></a></td>';
                                                    
//                                                    $cad .='<td><a href="javascript:void(0);" onclick="javascript:signal.delete(\''.$signal->ID.'\')" > <span class="dashicons dashicons-trash"></span></a></td>';
                                        
                                                    
                                                    $cad.='</tr>';
                                        
                            }
                            $prom_rr_g=round($suma_rr_g/$num_rr_g);
                        }
                        
                        $suma_pip = calculaSumaPip($cont_pips_bien,$cont_pips_mal); //funcion que calcula el total de pip ganados o perdidos
                        
                        $cad .="<tr><td colspan='8' class='aling_total'><b>TOTAL</b></td><td>".$prom_rr_g." ".$num_rr_g."</td><td colspan='3'></td>".$suma_pip."</tr>";
			$cad .='</tbody>
			</table>
		</div>
	</div>
</div>';
    return $cad;
}

//funcion que devuelve iniciales de la columna direction y order type
function convierteIniciales($data,$column){
    $cad='';
    if($column == 'direction'){
    switch ($data) {
                
                    case 'Pending Order':
                                 $cad='PO';       
                    break;    
                    
                    
                    default:
                        $cad = $data;
                    break; 
        }
    }elseif($column == 'order_type'){
        switch ($data) {
                    case 'Buy Limit': 
                                        $cad='BL'; 
                    break; 
                
                    case 'Sell Limit':
                                        $cad='SL'; 
                    break; 
                
                    case 'Buy Stop':
                                        $cad='BS'; 
                    break;    
                    
                    case 'Sell Stop':
                                         $cad='SS'; 
                    break; 
                    
                    default:
                        $cad = $data;
                    break; 
        }
    }    
    return $cad;
}
//TABLA PARA EL membership
function draw_table_signal_membership(){
$flag='';    //beeps
//updateTableSignals(); 
$flag=updateTableSignals_membership(); //beeps
global $wpdb;
$cont_pips_bien=0;
$cont_pips_mal=0;
$suma_rr_g=0;
$num_rr_g=0;
$prom_rr_g=0;
$value=getNroSignals('membership'); //obtnemos el numero de señales que se va a mostrar en la tabla membership

$data = $wpdb->get_results( 
                "SELECT *
                 FROM ".$wpdb->prefix ."signals t1
                 INNER JOIN ".$wpdb->prefix ."signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price)
                 ORDER BY ID desc 
                 LIMIT ".$value->nro_registros); 

$cad = "";

if($flag != ''){//beeps
    //$cad .= "<audio id='play' src='http://www.soundjay.com/button/beep-0".$flag.".wav'></audio>";//beeps
    $cad .= "<audio id='play' src='http://www.soundjay.com/button/button-".$flag.".wav'></audio>";//beeps
//    $cad .= $flag;//beeps
    $cad .="<script>document.getElementById('play').play();</script>";//beeps
}//beeps

    $cad .= '<div class="container-fluid">
	<div class="row">
	            <div class="col-md-12"> 
			<table class="table" id="table_result_private">
				<thead>
					<tr>
						<th class="text-center" style="display:none">
							ID 
						</th>
						<th class="text-center">
							DATE / TIME
						</th>
						<!--<th class="text-center">
							SIGNAL TIME
						</th>-->
						<!--<th class="text-center">
							DIRECTION
						</th>-->
						<th class="text-center">
							ORDER TYPE 
						</th>
						<th class="text-center">
							ASSET
						</th>
                                                <th class="text-center">
							METHOD
						</th>
						<th class="text-center">
							ENTRY PRICE
						</th>
						<th class="text-center">
							STOP LOSS
						</th>
						<th class="text-center">
							TAKE PROFIT
						</th>
						<th class="text-center">
							R/R
						</th>
                                                <th class="text-center">
							CLOSING PRICE
						</th>
                                                <th class="text-center">
							CLOSING TIME
						</th>
						<!--<th class="text-center">
							RESULT
						</th>-->
						<th class="text-center">
							PIPS
						</th>';
				   $cad .= '</tr>
				</thead>';
    
			$cad .='<tbody>';
                        if(count($data) > 0){ //validamos en caso de que no exista datos registrados en la BD
                            foreach ( $data as $signal ){
                            $result='';
                            $style_pips='';
                            $style_ok_po='';
                            
                            $precio = $signal->price_signal;
                            $stop_loss = $signal->stop_loss;
                            $take_profit = $signal->take_profit;
                            $cod_op_g = $signal->cod_op;
                            
                            if($signal->address == 'Pending Order'){ //validamos para que entry price muestre orden pendiente cuando sea el caso
                                $precio=$signal->orden_pendiente;

                                if($signal->cod_op == 1 && $signal->cancel == 0 && $signal->result == 0){
//                                    $style_ok_po="style='background-color:#00cc00;color:#fff;font-weight: bold;'";
                                    $style_ok_po="class='yes_green'";
                                }
                                
                            }

                            switch ($signal->result) {
                                case 2:
                                        $result ="<span class='dashicons dashicons-yes color_verde'></span>"; 
                                        //$style_pips="class='yes_green'";
                                        $style_pips="style='color: #00cc00;font-weight: bold;'";
                                        $cont_pips_bien= $cont_pips_bien+$signal->pips; //cuenta los pips ganados
                                break;
                                case 1:
                                        $result ="<span class='dashicons dashicons-no color_rojo'></span>"; 
                                        //$style_pips="class='no_red'";
                                        $style_pips="style='color: red;font-weight: bold;'";
                                        $cont_pips_mal= $cont_pips_mal+$signal->pips; //cuenta los pips perdidos
                                break;

                                default:
                                         $result ="<span class='dashicons dashicons-minus color_am'></span>"; 
                                break;
                            }
                            if($signal->closing_time){
                                $closing_time=$signal->closing_time;
                            }else{
                                $closing_time="<span class='dashicons dashicons-minus color_am'></span>";
                            }
                            //validamos para cuando la calidad sea 4 o 5 se pinte de color verde
                            if($signal->quality == 4 || $signal->quality == 5){
                            $class_quality="class='yes_green'";
                            }else{
                                $class_quality='';
                            }
                            if($signal->address=="Pending Order"){
                                if(substr($signal->type_of_order,0,1)=="S"){
                                    $order_type_e="<span class='dashicons dashicons-arrow-down-alt color_rojo'></span>"." &nbsp;&nbsp; S ".substr($signal->type_of_order,strpos($signal->type_of_order, ' ')+1,1);
                                    if($signal->result==0){    
                                        if($cod_op_g==1){
                                            $closing_price_g = $signal->price_sell;
                                            if($closing_price_g<=$precio){
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$precio)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$precio)*10000;
                                                }
                                                $style_pips="style='color: #00cc00;font-weight: bold;'";
                                            }else{
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$precio)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$precio)*10000;
                                                }
                                                $style_pips="style='color: #ff0000;font-weight: bold;'";
                                            }      
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }else if($signal->result==3){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a >'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #0000ff;font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }
                                else{
                                    $order_type_e="<span class='dashicons dashicons-arrow-up-alt color_verde'></span>"." &nbsp;&nbsp; B ".substr($signal->type_of_order,strpos($signal->type_of_order, ' ')+1,1);
                                    if($signal->result==0){   
                                        if($cod_op_g==1){
                                            $closing_price_g = $signal->price_buy;
                                            if($closing_price_g>=$signal->orden_pendiente){
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*10000;
                                                }
                                                $style_pips="style='color: #00cc00;font-weight: bold;'";
                                            }else{
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*10000;
                                                }
                                                $style_pips="style='color: #ff0000;font-weight: bold;'";
                                            }
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }else if($signal->result==3){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a >'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #0000ff;font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }                            
                            }else{
                                if($signal->address=="Sell"){ 
                                    $order_type_e="<span class='dashicons dashicons-arrow-down-alt color_rojo'></span>"." &nbsp;&nbsp; ".$signal->type_of_order;
                                    if($signal->result==0){  
                                        $closing_price_g = $signal->price_sell;
                                        if($closing_price_g<=$precio){
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #00cc00;font-weight: bold;'";
                                        }else{
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }
                                else{
                                    $order_type_e="<span class='dashicons dashicons-arrow-up-alt color_verde'></span>"." &nbsp;&nbsp; ".$signal->type_of_order;
                                    if($signal->result==0){ 
                                        $closing_price_g = $signal->price_buy;
                                        if($closing_price_g>=$precio){
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #00cc00;font-weight: bold;'";
                                        }else{
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }
                            }
                            if($signal->result>0){
                                $suma_rr_g+=$signal->take_profit/$signal->stop_loss;
                                $num_rr_g++;
                            }
                            $pips_g=abs(round($pips_g,2));
                                        $cad.='<tr class="active">
                                                   <td style="display:none">'.$signal->ID.'</td>
                                                   <td>'.$signal->date.' / '.$signal->time.'</td>
                                                    <!--<td>'.$signal->time.'</td>-->
                                                    <!--<td>'.convierteIniciales($signal->address,'direction').'</td>
                                                    <td>'.convierteIniciales($signal->type_of_order,'order_type').'</td>-->
                                                    <td>'.$order_type_e.'</td>
                                                    <td class="color_text">'.$signal->asset.'</td>
                                                    <td ><a target="_blank" href="'.$signal->method_link.'">'.$signal->method.'</a></td>
                                                    <td '.$style_ok_po.'>'.digitos($precio,$signal->cod_entry_price).'</td>
                                                    <td>'.$stop_loss.'</td>
                                                    <td>'.$take_profit.'</td>
                                                    <td '.$class_quality.'>'.round($take_profit/$stop_loss,1).'<a style="color:#0077ff" href="'.$signal->rr_link.'">(?)</a></td>
                                                    <td>'.$closing_price_c.'</td>
                                                    <td>'.$closing_time.'</td>
                                                    <!--<td>'.$result.'</td>-->
                                                    <td '.$style_pips.'>'.$signal->pips.' '.$pips_g.'</td>';
                                        $cad.='</tr>';
                                        
                            }
                            $prom_rr_g=round($suma_rr_g/$num_rr_g);
                        }
                        
                        $suma_pip = calculaSumaPip($cont_pips_bien,$cont_pips_mal); //funcion que calcula el total de pip ganados o perdidos
                        
                        $cad .="<tr><td colspan='7' class='aling_total'><b>TOTAL</b></td><td>".$prom_rr_g."</td><td colspan='3'>".$suma_pip."</tr>";
			$cad .='</tbody>
			</table>
		</div>
	</div>
</div>';
    return $cad;
}

//TABLA PARA EL membership
function draw_table_signal_home(){
    
updateTableSignals(); 
global $wpdb;
$cont_pips_bien=0;
$cont_pips_mal=0;
$suma_rr_g=0;
$num_rr_g=0;
$prom_rr_g=0;
$value=getNroSignals('home'); //obtnemos el numero de señales que se va a mostrar en la tabla home

$data = $wpdb->get_results( 
                "SELECT *
                 FROM ".$wpdb->prefix ."signals t1
                 INNER JOIN ".$wpdb->prefix ."signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price)
                 WHERE t1.result > 0
                 ORDER BY ID desc 
                 LIMIT ".$value->nro_registros); 

$cad = "";

    $cad .= '<div class="container-fluid">
	<div class="row">
	            <div class="col-md-12"> 
			<table class="table" id="table_result_home">
				<thead>
					<tr>
						<th class="text-center" style="display:none">
							ID 
						</th>
						<th class="text-center">
							DATE / TIME
						</th>
						<!--<th class="text-center">
							SIGNAL TIME
						</th>-->
						<!--<th class="text-center">
							DIRECTION
						</th>-->
						<th class="text-center">
							ORDER TYPE 
						</th>
						<th class="text-center">
							ASSET
						</th>
                                                <th class="text-center">
							METHOD
						</th>
						<th class="text-center">
							ENTRY PRICE
						</th>
						<th class="text-center">
							STOP LOSS
						</th>
						<th class="text-center">
							TAKE PROFIT
						</th>
						<th class="text-center">
							R/R
						</th>
                                                <th class="text-center">
							CLOSING PRICE
						</th>
                                                <th class="text-center">
							CLOSING TIME
						</th>
						<!--<th class="text-center">
							RESULT
						</th>-->
						<th class="text-center">
							PIPS
						</th>';
				   $cad .= '</tr>
				</thead>';
    
			$cad .='<tbody>';
                        if(count($data) > 0){ //validamos en caso de que no exista datos registrados en la BD
                            foreach ( $data as $signal ){
                            $result='';
                            $style_pips='';
                            $style_ok_po='';
                            
                            $precio = $signal->price_signal;
                            $stop_loss = $signal->stop_loss;
                            $take_profit = $signal->take_profit;
                            $cod_op_g = $signal->cod_op;
                            
                            if($signal->address == 'Pending Order'){ //validamos para que entry price muestre orden pendiente cuando sea el caso
                                $precio=$signal->orden_pendiente;

                                if($signal->cod_op == 1 && $signal->cancel == 0 && $signal->result == 0){
                                    $style_ok_po="class='yes_green'";
                                }
                                
                            }

                            switch ($signal->result) {
                                case 2:
                                        $result ="<span class='dashicons dashicons-yes color_verde'></span>"; 
                                        //$style_pips="class='yes_green'";
                                        $style_pips="style='color: #00cc00;font-weight: bold;'";
                                        $cont_pips_bien= $cont_pips_bien+$signal->pips; //cuenta los pips ganados
                                break;
                                case 1:
                                        $result ="<span class='dashicons dashicons-no color_rojo'></span>"; 
                                        //$style_pips="class='no_red'";
                                        $style_pips="style='color: red;font-weight: bold;'";
                                        $cont_pips_mal= $cont_pips_mal+$signal->pips; //cuenta los pips perdidos
                                break;

                                default:
                                         $result ="<span class='dashicons dashicons-minus color_am'></span>"; 
                                break;
                            }
                            if($signal->closing_time){
                                $closing_time=$signal->closing_time;
                            }else{
                                $closing_time="<span class='dashicons dashicons-minus color_am'></span>";
                            }
                            //validamos para cuando la calidad sea 4 o 5 se pinte de color verde
                            if($signal->quality == 4 || $signal->quality == 5){
                            $class_quality="class='yes_green'";
                            }else{
                                $class_quality='';
                            }
                            if($signal->address=="Pending Order"){
                                if(substr($signal->type_of_order,0,1)=="S"){
                                    $order_type_e="<span class='dashicons dashicons-arrow-down-alt color_rojo'></span>"." &nbsp;&nbsp; S ".substr($signal->type_of_order,strpos($signal->type_of_order, ' ')+1,1);
                                    if($signal->result==0){    
                                        if($cod_op_g==1){
                                            $closing_price_g = $signal->price_sell;
                                            if($closing_price_g<=$precio){
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$precio)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$precio)*10000;
                                                }
                                                $style_pips="style='color: #00cc00;font-weight: bold;'";
                                            }else{
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$precio)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$precio)*10000;
                                                }
                                                $style_pips="style='color: #ff0000;font-weight: bold;'";
                                            }      
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }else if($signal->result==3){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a >'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #0000ff;font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }
                                else{
                                    $order_type_e="<span class='dashicons dashicons-arrow-up-alt color_verde'></span>"." &nbsp;&nbsp; B ".substr($signal->type_of_order,strpos($signal->type_of_order, ' ')+1,1);
                                    if($signal->result==0){   
                                        if($cod_op_g==1){
                                            $closing_price_g = $signal->price_buy;
                                            if($closing_price_g>=$signal->orden_pendiente){
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*10000;
                                                }
                                                $style_pips="style='color: #00cc00;font-weight: bold;'";
                                            }else{
                                                $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                                if( substr( $signal->asset, -3) == 'JPY'  ){
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*100;
                                                }
                                                else{
                                                    $pips_g = ($closing_price_g-$signal->orden_pendiente)*10000;
                                                }
                                                $style_pips="style='color: #ff0000;font-weight: bold;'";
                                            }
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }else if($signal->result==3){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a >'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #0000ff;font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }                            
                            }else{
                                if($signal->address=="Sell"){ 
                                    $order_type_e="<span class='dashicons dashicons-arrow-down-alt color_rojo'></span>"." &nbsp;&nbsp; ".$signal->type_of_order;
                                    if($signal->result==0){  
                                        $closing_price_g = $signal->price_sell;
                                        if($closing_price_g<=$precio){
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #00cc00;font-weight: bold;'";
                                        }else{
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }
                                else{
                                    $order_type_e="<span class='dashicons dashicons-arrow-up-alt color_verde'></span>"." &nbsp;&nbsp; ".$signal->type_of_order;
                                    if($signal->result==0){ 
                                        $closing_price_g = $signal->price_buy;
                                        if($closing_price_g>=$precio){
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:#00cc00">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #00cc00;font-weight: bold;'";
                                        }else{
                                            $closing_price_c=substr($closing_price_g."", 0,-3).'<a style="color:red">'.substr($closing_price_g."", -3);
                                            if( substr( $signal->asset, -3) == 'JPY'  ){
                                                $pips_g = ($closing_price_g-$precio)*100;
                                            }
                                            else{
                                                $pips_g = ($closing_price_g-$precio)*10000;
                                            }
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                    }else{
                                        if($signal->result==2){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:#00cc00">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #00ff00;font-weight: bold;'";
                                        }else if($signal->result==1){
                                            $closing_price_c=substr($signal->closing_price."", 0,-3).'<a style="color:red">'.substr($signal->closing_price."", -3);
                                            $style_pips="style='color: #ff0000;font-weight: bold;'";
                                        }
                                        $pips_g=$signal->pips;
                                    }
                                }
                            }
                            if($signal->result>0){
                                $suma_rr_g+=$signal->take_profit/$signal->stop_loss;
                                $num_rr_g++;
                            }
                            $pips_g=abs(round($pips_g,2));
                                        $cad.='<tr class="active">
                                                   <td style="display:none">'.$signal->ID.'</td>
                                                   <td>'.$signal->date.' / '.$signal->time.'</td>
                                                    <!--<td>'.$signal->time.'</td>-->
                                                    <!--<td>'.convierteIniciales($signal->address,'direction').'</td>
                                                    <td>'.convierteIniciales($signal->type_of_order,'order_type').'</td>-->
                                                    <td>'.$order_type_e.'</td>
                                                    <td class="color_text">'.$signal->asset.'</td>
                                                    <td ><a target="_blank" href="'.$signal->method_link.'">'.$signal->method.'</a></td>
                                                    <td '.$style_ok_po.'>'.digitos($precio,$signal->cod_entry_price).'</td>
                                                    <td>'.$stop_loss.'</td>
                                                    <td>'.$take_profit.'</td>
                                                    <td '.$class_quality.'>'.round($take_profit/$stop_loss,1).'<a style="color: #0077ff" href="'.$signal->rr_link.'">(?)</a></td>
                                                    <td>'.$closing_price_c.'</td>
                                                    <td>'.$closing_time.'</td>
                                                    <!--<td>'.$result.'</td>-->
                                                    <td '.$style_pips.'>'.$signal->pips.' '.$pips_g.'</td>';
                                        $cad.='</tr>';
                                        
                            }
                            $prom_rr_g=round($suma_rr_g/$num_rr_g);
                        }
                        
                        $suma_pip = calculaSumaPip($cont_pips_bien,$cont_pips_mal); //funcion que calcula el total de pip ganados o perdidos
                        
                        $cad .="<tr><td colspan='7' class='aling_total'><b>TOTAL</b></td><td>".$prom_rr_g."</td><td colspan='3'>".$suma_pip."</tr>";
			$cad .='</tbody>
			</table>
		</div>
	</div>
</div>';
    return $cad;
}
//add_shortcode("signals", "singnal_shortcode");
function calculaSumaPip($cont_pips_bien,$cont_pips_mal){
        $cad='';
        $aux=0;
        if( $cont_pips_bien > 0 || $cont_pips_mal > 0 ){
            if($cont_pips_bien > $cont_pips_mal){
                $data['mayor']=$cont_pips_bien;
                $data['menor']=$cont_pips_mal;
                $aux=true;

            }else{
                $data['mayor']=$cont_pips_mal;
                $data['menor']=$cont_pips_bien;
                $aux=false;
            }

            $sum_pips = $data['mayor'] - $data['menor'];
            
            /*Sacar porcentaje*/
            $suma_total= $data['mayor'] + $data['menor'];
            
            if($aux){
                /*Calculamos porcentajes*/
                $porcentaje=$sum_pips/$suma_total;
                $porcentaje=abs(round($porcentaje,2));
                //$porcentaje=abs(round($porcentaje * 10)/10);
                $porcentaje=$porcentaje*100;
//                $cad ="<td style='background-color:#00cc00;color:#fff;font-weight: bold;'>".$sum_pips."</td><td>".$porcentaje."%</td>";
                $cad ="<td class='yes_green'>".$sum_pips."</td><!--<td>".$porcentaje."%</td>-->";
            }else{
                /*Calculamos porcentajes*/
                $porcentaje=$sum_pips/$suma_total;
                $porcentaje=abs(round($porcentaje,2));
                //$porcentaje=abs(round($porcentaje * 10)/10);
                $porcentaje=$porcentaje*100;
//                $cad ="<td style='background-color:#FB0000;color:#fff;font-weight: bold;'>".$sum_pips."</td><td>".$porcentaje."%</td>";
                $cad ="<td class='no_red'>".$sum_pips."</td><!--<td>".$porcentaje."%</td>-->";
            }


        }else{
            $cad ="<td style='font-weight: bold;'>0</td><td>0%</td>";
        }
        return $cad;
}

function calculaResultado($id,$cod_asset,$stop_loss,$take_profit,$signal,$tipo_signal,$orden_pendiente){
    global $wpdb;
    $asset = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix ."signals_price"." WHERE cod_entry_price =".$cod_asset );
    
    return calculaResultadoSeñal($id,$tipo_signal,$signal,$take_profit,$stop_loss,$asset->price_sell,$asset->price_buy,$orden_pendiente,0);
    
} 

//Funcion que donde se implementa el algoritmo
function calculaResultadoSeñal($id,$tipo_signal,$signal,$take_profit,$stop_loss,$price_sell,$price_buy,$orden_pendiente,$cod_op){
    global $wpdb;
    $resultado=0;
    $table_signals_wp= $wpdb->prefix . "signals";
    if ($take_profit != $stop_loss){
        switch ($tipo_signal) {
            case 'Spot':
                            if($signal == 'Sell'){ // preguntamos si la señal es Vender
                                if($price_sell <= $take_profit){
                                    $resultado = 2; //donde 2 es bien
                                }else{
                                    if($price_sell >= $stop_loss){
                                      $resultado = 1; //1 es malo  
                                    }
                                }
                               
                            }else{
                                if($signal == 'Buy'){ //Preguntamos si es Comprar
                                    if($price_buy >= $take_profit){
                                        $resultado = 2; 
                                    }else{
                                        if($price_buy <= $stop_loss){
                                            $resultado = 1; //1 es malo  
                                        }
                                    }    
                                }    
                            }    
                break;
            
            case 'Buy Stop':    
                            if($signal == 'Pending Order'){
                                    if($cod_op == 1){
                                            if ($price_buy >= $take_profit) {
                                                $resultado = 2;
                                            } else {
                                                if ($price_buy <= $stop_loss) {
                                                    $resultado = 1; //1 es malo  
                                                }
                                            }
                                    }else{
                                        if($orden_pendiente <= $price_buy ){
                                            if($id != null){
                                                $wpdb->update($table_signals_wp, array('cod_op' => 1), array('ID' => $id));
                                            }
                                        }
                                    }
                            }
                break;

            case 'Buy Limit':
                            if($signal == 'Pending Order'){
                                    if($cod_op == 1){
                                            if ($price_buy >= $take_profit) {
                                                $resultado = 2;
                                            } else {
                                                if ($price_buy <= $stop_loss) {
                                                    $resultado = 1; //1 es malo  
                                                }
                                            }
                                    }else{
                                        if($orden_pendiente >= $price_buy ){
                                            if($id != null){
                                                $wpdb->update($table_signals_wp, array('cod_op' => 1), array('ID' => $id));
                                            }
                                        }
                                    }
                            }
                            /*    
                            if($signal === 'Pending Order'){
                                if($orden_pendiente == 0){
                                    if($price_buy >= $take_profit){
                                        $resultado = 2; 
                                    }else{
                                        if($price_buy <= $stop_loss){
                                            $resultado = 1; //1 es malo  
                                        }
                                    }  
                                }else{
                                    if($orden_pendiente == $price_buy ){
                                        if($id != null){
                                           $wpdb->update($table_signals_wp, array('orden_pendiente' => 0), array('ID' => $id));
                                        }
                                    }
                                }
                            } 
                             * */
            break;

            /*case 'Buy Stop':
                            if($signal === 'Pending Order'){
                                if($orden_pendiente == 0){
                                    if($price_buy >= $take_profit){
                                        $resultado = 2; 
                                    }else{
                                        if($price_buy <= $stop_loss){
                                            $resultado = 1; //1 es malo  
                                        }
                                    }  
                                }else{
                                    if($orden_pendiente == $price_buy ){
                                        if($id != null){
                                           $wpdb->update($table_signals_wp, array('orden_pendiente' => 0), array('ID' => $id));
                                        }
                                    }
                                }
                            }    
            break;*/
            
            case 'Sell Limit':
                            if($signal == 'Pending Order'){
                                    if($cod_op == 1){
                                            if ($price_sell <= $take_profit) {
                                                $resultado = 2;
                                            } else {
                                                if ($price_sell >= $stop_loss) {
                                                    $resultado = 1; //1 es malo  
                                                }
                                            }
                                    }else{
                                        if($orden_pendiente <= $price_sell ){
                                            if($id != null){
                                                $wpdb->update($table_signals_wp, array('cod_op' => 1), array('ID' => $id));
                                            }
                                        }
                                    }
                            }
            break;


            case 'Sell Stop':
                            
                            if($signal == 'Pending Order'){
                                    if($cod_op == 1){
                                            if ($price_sell <= $take_profit) {
                                                $resultado = 2;
                                            } else {
                                                if ($price_sell >= $stop_loss) {
                                                    $resultado = 1; //1 es malo  
                                                }
                                            }
                                    }else{
                                        if($orden_pendiente >= $price_sell ){
                                            if($id != null){
                                                $wpdb->update($table_signals_wp, array('cod_op' => 1), array('ID' => $id));
                                            }
                                        }
                                    }
                            }
                
                            /*if($signal === 'Pending Order'){
                                if($orden_pendiente == 0){
                                    if ($price_sell <= $take_profit) {
                                            $resultado = 2; //donde 2 es bien
                                        } else {
                                            if ($price_sell >= $stop_loss) {
                                                $resultado = 1; //1 es malo  
                                            }
                                        }
                                }else{
                                    if($orden_pendiente == $price_sell ){
                                        if($id != null){
                                           $wpdb->update($table_signals_wp, array('orden_pendiente' => 0), array('ID' => $id));
                                        }
                                    }
                                }
                            } */   
            break;

           /* case 'Sell Limit':
                            if($signal === 'Pending Order'){
                                if($orden_pendiente == 0){
                                    if ($price_sell <= $take_profit) {
                                            $resultado = 2; //donde 2 es bien
                                        } else {
                                            if ($price_sell >= $stop_loss) {
                                                $resultado = 1; //1 es malo  
                                            }
                                        }
                                }else{
                                    if($orden_pendiente == $price_sell ){
                                        if($id != null){
                                           $wpdb->update($table_signals_wp, array('orden_pendiente' => 0), array('ID' => $id));
                                        }
                                    }
                                }
                            }      
            break;*/


            default:
                break;
        }
    }
    return $resultado;
    
}    

//Esta funcion solo funciona una vez cuando se instala el plugin
function generaPrecio($moneda){
    
    $moneda = explode("/", $moneda);
    
    $cambio = "http://65.181.127.143/ss/q.php?symbols=".$moneda[0].$moneda[1]; //esta url nos devuelve el tipo de cambio de venta y compra
    $cambioJSON = file_get_contents($cambio);
    $cambios = json_decode($cambioJSON);
//    $valor = explode(" ", $cambios[0]);

    return $cambios;
}
add_action('wp_ajax_update_precios', 'actualizaPrecios');  //funcion que actuliza precios
//Esta funcion actualiza los precios de las monedas
function actualizaPrecios(){
    global $wpdb;
    //$ch = curl_init('https://forex.1forge.com/1.0.3/quotes?pairs=EURUSD,GBPUSD,AUDUSD,NZDUSD,USDCAD,USDJPY,EURJPY,EURAUD,GBPJPY,GBPAUD,AUDNZD,AUDJPY&api_key=T6xtYb1asOJv3dktmqCYGFbckRMP8Ugm');
    $ch = curl_init('https://forex.1forge.com/1.0.3/quotes?pairs=EURUSD,GBPUSD,AUDUSD,NZDUSD,USDCAD,USDJPY,EURJPY,EURAUD,GBPJPY,GBPAUD,AUDNZD,AUDJPY&api_key=49rv3u9Xjdohn74vlhirYMkk9O1UPVEF');
    //$ch = curl_init('https://forex.1forge.com/1.0.3/quotes?pairs=EURUSD,GBPUSD,AUDUSD,NZDUSD,USDCAD,USDJPY,EURJPY,EURAUD,GBPJPY,GBPAUD,AUDNZD,AUDJPY&api_key=sPXBxhUWSVjiRlZGG5MmFbW4ooIN1zqF');
    //$ch = curl_init('https://forex.1forge.com/1.0.3/quotes?pairs=EURUSD,GBPUSD,AUDUSD,NZDUSD,USDCAD,USDJPY,EURJPY,EURAUD,GBPJPY,GBPAUD,AUDNZD,AUDJPY&api_key=1XSbz2osTYy4hJILXnIPI18BsNgOnco7');
    //$ch = curl_init('https://forex.1forge.com/1.0.3/quotes?pairs=EURUSD,GBPUSD,AUDUSD,NZDUSD,USDCAD,USDJPY,EURJPY,EURAUD,GBPJPY,GBPAUD,AUDNZD,AUDJPY&api_key=XkcUf5UO1JTl6vKt3yeN3zTolQaCKNPU');
    //$ch = curl_init('https://forex.1forge.com/1.0.3/quotes?pairs=EURUSD,GBPUSD,AUDUSD,NZDUSD,USDCAD,USDJPY,EURJPY,EURAUD,GBPJPY,GBPAUD,AUDNZD,AUDJPY&api_key=5kypQttfi0GtmfBMUff0YdE4p60nZrLR');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($ch);
    //$json='[{"symbol":"EURUSD","price":1.24308,"bid":1.24288,"ask":1.24328,"timestamp":1517068823},{"symbol":"GBPUSD","price":1.41648,"bid":1.41627,"ask":1.41668,"timestamp":1517068823},{"symbol":"AUDUSD","price":0.81114,"bid":0.81086,"ask":0.81142,"timestamp":1517068823},{"symbol":"NZDUSD","price":0.73589,"bid":0.7358,"ask":0.73598,"timestamp":1517068823},{"symbol":"USDCAD","price":1.2318,"bid":1.23142,"ask":1.23218,"timestamp":1517068823},{"symbol":"USDJPY","price":108.6105,"bid":108.589,"ask":108.632,"timestamp":1517068823},{"symbol":"EURJPY","price":135.021,"bid":134.975,"ask":135.067,"timestamp":1517068823},{"symbol":"EURAUD","price":1.53249,"bid":1.53187,"ask":1.5331,"timestamp":1517068823},{"symbol":"GBPJPY","price":153.908,"bid":153.841,"ask":153.975,"timestamp":1517068823},{"symbol":"GBPAUD","price":1.74551,"bid":1.74472,"ask":1.7463,"timestamp":1517068823},{"symbol":"AUDNZD","price":1.10209,"bid":1.10159,"ask":1.10258,"timestamp":1517068823},{"symbol":"AUDJPY","price":88.102,"bid":88.061,"ask":88.143,"timestamp":1517068823}]';
    curl_close($ch);
    $exchangeRates = json_decode($json, true);
    foreach($exchangeRates as $value){
        $asset = $value['symbol'];
        $modena_venta = $value['bid'];
        $modena_compra = $value['ask'];
        $todayh = getdate();
        $fecha=convierteFormatoFecha($todayh[year].".".$todayh[mon].".".$todayh[mday]);
        $hora=$todayh[hours].":".$todayh[minutes].":".$todayh[seconds];
        $table_signals_price = $wpdb->prefix . "signals_price";
        $wpdb->update($table_signals_price, array('price_sell' => $modena_venta,'price_buy'=>$modena_compra,'date_price' =>$fecha,'time_price'=>$hora,'up_down'=>"up"), array('asset' => $asset));   
    }
    /*global $wpdb;
    $url_precios_update="http://65.181.127.143/ss/q.php?symbols=EURUSD,GBPUSD,AUDUSD,NZDUSD,USDCAD,USDJPY,EURJPY,EURAUD,GBPJPY,GBPAUD,AUDNZD,AUDJPY";
    $cambioJSON = file_get_contents($url_precios_update);
    $cambios = json_decode($cambioJSON);
    
    foreach ($cambios as $key => $value) {
        if($key <= 11){
            $datos = explode(" ", $value);
            
            $updown=$datos[0];
            $asset=$datos[1];
            $modena_venta=$datos[2];
            $modena_compra=$datos[3];
            $fecha=convierteFormatoFecha($datos[4]);
            $hora = $datos[5];
            
            $table_signals_price = $wpdb->prefix . "signals_price";
            $wpdb->update($table_signals_price, array('price_sell' => $modena_venta,'price_buy'=>$modena_compra,'date_price' =>$fecha,'time_price'=>$hora,'up_down'=>$updown), array('asset' => $asset));
        }
    }*/
}
function actualizaPrecios_bk(){
    global $wpdb;
        
        $monedas = array("EUR/USD","GBP/USD","AUD/USD","NZD/USD","USD/CAD","USD/JPY","EUR/JPY","EUR/AUD","GBP/JPY","GBP/AUD","AUD/NZD","AUD/JPY");
        
        foreach ($monedas as $value) {
            
            $datos=generaPrecio($value);
            
            $price = explode(" ", $datos[0]);
            $valores_tiempo = explode(" ", $datos[1]);
            
            $modena_venta=$price[2];
            $modena_compra=$price[3];
            
            /* Hora y Fecha en que se recibio los valores*/
            $fecha=$valores_tiempo[0];
            $hora=$valores_tiempo[1];

            $fecha=convierteFormatoFecha($fecha);
            //actualizamos campos en la tabla
            $table_signals_price = $wpdb->prefix . "signals_price";
            $wpdb->update($table_signals_price, array('price_sell' => $modena_venta,'price_buy'=>$modena_compra,'date_price' =>$fecha,'time_price'=>$hora), array('asset' => $value));
            
        }
} 


add_action('wp_ajax_close_market', 'closeMarket');  //funcion que actuliza precios
//Esta funcion hace el cierre por cierre de mercado de las operaciones vigentes
function closeMarket(){
    global $wpdb;
    $pips=0;
    $data = $wpdb->get_results(
            "SELECT *
                    FROM " . $wpdb->prefix . "signals t1
                    INNER JOIN " . $wpdb->prefix . "signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price)
                    WHERE t1.result = 0 AND t1.cancel = 0
                    ORDER BY ID DESC"
    );
    if (count($data) > 0) {
        foreach ($data as $signal) {
            $id_signal = $signal->ID;
            $asset = $signal->asset;
            $tipo_signal = $signal->type_of_order;
            $address = $signal->address;
            $take_profit = $signal->take_profit;
            $stop_loss = $signal->stop_loss;
            //$resultado = $signal->result;
            $orden_pendiente = $signal->orden_pendiente;
            $price_signal=$signal->price_signal;
            $cod_op=$signal->cod_op;
            
            $cod_price=$signal->cod_entry_price;

            $resultado=0;
            //$id_signal=$_POST['id_signal'];
            //$valor=$_POST['valor'];
            //$cod_price=$_POST['cod_price'];
            //$tipo_signal=$_POST['tipo_signal'];
            //$signal=$_POST['signal'];
            //$stop_loss=$_POST['stop_loss'];
            //$take_profit=$_POST['take_profit'];
            //$price_signal=$_POST['price_signal'];
            //$orden_pendiente=$_POST['orden_pendiente'];
            //$cod_op=$_POST['cod_op'];

            $precio_actual=generaPriceSignal($cod_price,$tipo_signal,$address); //Obtiene el precio actual de la moneda

            if( $address != 'Pending Order' ){
            
                $resultado=calculaMayor($stop_loss,$price_signal,$precio_actual,'SL');
                
                if($resultado == 0){
                   $resultado=calculaMayor($take_profit,$price_signal,$precio_actual,'TP');
                }
            }else{
                if($cod_op > 0){ //validamos si ya toco at_price
                    
                    $resultado=calculaMayor($stop_loss,$orden_pendiente,$precio_actual,'SL');
                    
                    if($resultado == 0){
                       $resultado=calculaMayor($take_profit,$orden_pendiente,$precio_actual,'TP');
                    }
                    
                    $price_signal=$orden_pendiente;
                }else{
                    if($cod_op == 0){
                        $table_signals = $wpdb->prefix . "signals";
                        $wpdb->update($table_signals, array('cancel'=>1,'result'=>3), array('ID' => $id_signal));
                    }
                }
            }
            
            if($resultado > 0){
                    /*
                    if($resultado == 1){
                        $pips = ($stop_loss-$price_signal)*10000;
                    }else{
                        $pips = ($take_profit-$price_signal)*10000;
                    }
                    */

                if( $cod_price == 6 || $cod_price == 7 || $cod_price == 9 || $cod_price == 12){
                    $pips = ($precio_actual-$price_signal)*100;
                }            
                else{
                    $pips = ($precio_actual-$price_signal)*10000;
                }

                //$pips=abs(round($pips * 10)/10);
                $pips=abs(round($pips,2));
                
                $closing_price_g = $precio_actual;
                
                $table_signals = $wpdb->prefix . "signals";
                $wpdb->update($table_signals, array('result' => $resultado,'pips' =>$pips,'closing_price' =>$closing_price_g,'cancel'=>1), array('ID' => $id_signal));        
            }



            /*
            $resultado = calculaResultadoSeñal($id,$tipo_signal, $address, $take_profit, $stop_loss, $signal->price_sell, $signal->price_buy,$orden_pendiente,$cod_op);

            if ($resultado != 0) {
                
                if($address == 'Pending Order'){ // En caso de ordenes pendientes los pips se los realza en pase su at_price
                    $price_signal=$orden_pendiente;
                }
                
                if($resultado == 1){
                    if( substr( $asset, -3) == 'JPY'  ){
                        $pips = ($stop_loss-$price_signal)*100;
                    }
                    else{
                        $pips = ($stop_loss-$price_signal)*10000;
                    }
                }else{
                    if( substr( $asset, -3) == 'JPY'  ){
                        $pips = ($take_profit-$price_signal)*100;
                    }
                    else{
                        $pips = ($take_profit-$price_signal)*10000;
                    }
                }
                $pips=abs(round($pips * 10)/10);

                //$pips=abs($pips);
                
                $table_signals = $wpdb->prefix . "signals";
                $wpdb->update($table_signals, array('result' => $resultado,'pips' =>$pips), array('ID' => $signal->ID));
            }
            */
        }
        $cad = draw_table_signal();
        echo json_encode($cad);
        exit();
    }
}


function updateTableSignals() {
    global $wpdb;
    $pips=0;
    $data = $wpdb->get_results(
            "SELECT *
                    FROM " . $wpdb->prefix . "signals t1
                    INNER JOIN " . $wpdb->prefix . "signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price)
                    WHERE t1.result = 0 AND t1.cancel = 0
                    ORDER BY ID DESC"
    );
    if (count($data) > 0) {
        foreach ($data as $signal) {
            $id = $signal->ID;
            $asset = $signal->asset;
            $tipo_signal = $signal->type_of_order;
            $address = $signal->address;
            $take_profit = $signal->take_profit;
            $stop_loss = $signal->stop_loss;
            $take_profit_edit = $signal->take_profit_edit;
            $stop_loss_edit = $signal->stop_loss_edit;
            
            
            
            $resultado = $signal->result;
            $orden_pendiente = $signal->orden_pendiente;
            $price_signal=$signal->price_signal;
            $cod_op=$signal->cod_op;
            if($take_profit_edit != 0){
                //$take_profit = $take_profit_edit;
                conviertePIP($asset,$tipo_signal,$address,$price_signal,'TP',$take_profit_edit);
            }
            if($stop_loss_edit != 0){
                //$stop_loss = $stop_loss_edit;
                conviertePIP($asset,$tipo_signal,$address,$price_signal,'SL',$stop_loss_edit);
            }
            $cod_price=$signal->cod_entry_price;

            $resultado = calculaResultadoSeñal($id,$tipo_signal, $address, $take_profit, $stop_loss, $signal->price_sell, $signal->price_buy,$orden_pendiente,$cod_op);

            if ($resultado != 0) {
                
                if($address == 'Pending Order'){ // En caso de ordenes pendientes los pips se los realza en pase su at_price
                    $price_signal=$orden_pendiente;
                }
                
                if($resultado == 1){
                    if( substr( $asset, -3) == 'JPY'  ){
                        $pips = ($stop_loss-$price_signal)*100;
                    }
                    else{
                        $pips = ($stop_loss-$price_signal)*10000;
                    }
                }else{
                    if( substr( $asset, -3) == 'JPY'  ){
                        $pips = ($take_profit-$price_signal)*100;
                    }
                    else{
                        $pips = ($take_profit-$price_signal)*10000;
                    }
                }
                //$pips=abs(round($pips * 10)/10);
                $pips=abs(round($pips,2));
                
                $precio_actual=generaPriceSignal($cod_price,$tipo_signal,$address); //Obtiene el precio actual de la moneda
                $closing_price_g = $precio_actual;//EL CLOSING PRICE ES ES EL PRECIO QUE VARIA  
                
                //$pips=abs($pips);
                //generamos la hora en que llego su fin de la señal
                date_default_timezone_set("Europe/Berlin"); //muestra la fecha y hora de La Paz Bolivia
                $closing_time = date("H:i:s");
                
                $table_signals = $wpdb->prefix . "signals";
//                $wpdb->update($table_signals, array('result' => $resultado,'pips' =>$pips), array('ID' => $signal->ID));
                $wpdb->update($table_signals, array('result' => $resultado,'pips' =>$pips,'closing_price' =>$closing_price_g,'closing_time'=>$closing_time), array('ID' => $signal->ID));
                }
            }
        }
    }

function formatoCombroPrecios($cadena){
    $cadena1=substr($cadena,0,3);
    $cadena2=substr($cadena,3);
    
    return $cadena1." / ".$cadena2;
}

/*Funcion que convierte numeros a PIP*/
function conviertePIP($cod_asset,$tipo_signal,$signal,$precio_signal,$value,$sl_tp){

    //if( substr( $cod_asset, -3) != 'JPY'  ){
    if( $cod_asset == 6 || $cod_asset == 7 || $cod_asset == 9 || $cod_asset == 12){
        $cad = $sl_tp/100;
        $cad=abs(round($cad * 100)/100); 
        //$cad=abs(round($cad,2)); 
    }else{
        $cad = $sl_tp/10000;
        $cad=abs(round($cad * 10000)/10000); //redondeo a 5 decimales
        //$cad=abs(round($cad,2));
    }
    
    $resultado=0;
    $precio=$precio_signal;
//    echo "<br> --- cad ---->" . $cad;
//    echo "<br> --- precio ---->" . $precio;
    switch ($tipo_signal) {
                    case 'Spot':
                                       if ($signal == 'Sell') {
                                                 if($value == 'TP'){
                                                     $resultado = $precio-$cad;
                                                 }else{
                                                     $resultado = $precio+$cad;
                                                 }
                                        } else {
                                                if($value == 'TP'){
                                                    $resultado = $precio+$cad;
                                                }else{
                                                    $resultado = $precio-$cad;
                                                }
                                        }
                    break;
                    
                    case 'Buy Limit': 
                                        if($value == 'SL' || $value == 'PO'){
                                           $resultado = $precio-$cad;
                                        }else{
                                              $resultado = $precio+$cad; 
                                            }
                    break; 
                
                    case 'Sell Limit':
                                        if($value == 'SL' || $value == 'PO'){
                                           $resultado = $precio+$cad;
                                         }else{
                                               $resultado = $precio-$cad;
                                             }
                    break; 
                
                    case 'Buy Stop':
                                        if($value == 'TP' || $value == 'PO'){
                                           $resultado = $precio+$cad;
                                        }else{
                                                $resultado = $precio-$cad;
                                            }
                    break;    
                    
                    case 'Sell Stop':
                                         if($value == 'TP' || $value == 'PO'){
                                           $resultado = $precio-$cad;
                                         }else{
                                                $resultado = $precio+$cad;
                                             }
                    break; 
                    
                    default:
                    break; 
        }
        
        return $resultado;
}


/* Shortcode que va a mostrar la tabla */
function table_shortcode($atts) {
    extract(shortcode_atts(array(
                'view' => 'default',
       ), $atts));
    echo "<input class='view_table' type='hidden' value='{$view}'/><div class='table-signal'></div>";
      
    
//    echo "<div class='table-signal1'>".$view."</div>";
}
add_shortcode( 'signals', 'table_shortcode' );

/***********************************************/
/**************  BEEPS  ********************/
/***********************************************/
function updateTableSignals_membership() {
    global $wpdb;
    $pips=0;
    $flag='';
    $data = $wpdb->get_results(
            "SELECT *
                    FROM " . $wpdb->prefix . "signals t1
                    INNER JOIN " . $wpdb->prefix . "signals_price t2 ON(t2.cod_entry_price = t1.cod_entry_price)
                    WHERE t1.result = 0 AND t1.cancel = 0
                    ORDER BY ID DESC"
    );
    if (count($data) > 0) {
        foreach ($data as $signal) {
            $id = $signal->ID;
            $asset = $signal->asset;
            $tipo_signal = $signal->type_of_order;
            $address = $signal->address;
            $take_profit = $signal->take_profit;
            $stop_loss = $signal->stop_loss;
            $take_profit_edit = $signal->take_profit_edit;
            $stop_loss_edit = $signal->stop_loss_edit;
            $resultado = $signal->result;
            $orden_pendiente = $signal->orden_pendiente;
            $price_signal=$signal->price_signal;
            $cod_op=$signal->cod_op;
            $cancel=$signal->cancel;
            $beep=$signal->beep;
            if($take_profit_edit != 0){
                //$take_profit = $take_profit_edit;
                $take_profit=conviertePIP($asset,$tipo_signal,$address,$price_signal,'TP',$take_profit_edit);
            }
            if($stop_loss_edit != 0){
                //$stop_loss = $stop_loss_edit;
                $stop_loss=conviertePIP($asset,$tipo_signal,$address,$price_signal,'SL',$stop_loss_edit);
            }
            $cod_price=$signal->cod_entry_price;
            
            $resultado = calculaResultadoSeñal_membership($id,$tipo_signal, $address, $take_profit, $stop_loss, $signal->price_sell, $signal->price_buy,$orden_pendiente,$cod_op);
            
            /**************************************/
            /**************************************/
//            if($resultado == 1 || $resultado == 2){
            if($beep ==  0){
                
                switch ($tipo_signal) {
                    case 'Spot':
                        if ($address == 'Sell') { 
                           $flag=4;
                        } else {
                            if ($address == 'Buy') { 
                                 $flag=14;
                            }
                        }
                        break;
                    default:
                         $flag=13;
                    break;
                }
                
                $table_signals2 = $wpdb->prefix . "signals";
                if($signal->ID){
                  $wpdb->update($table_signals2, array('beep' =>1), array('ID' => $signal->ID));
                }
//                  $cad="<script>document.getElementById('play').play();</script>";
             }
             /**************************************/
            /**************************************/
            
            if ($resultado != 0) {
                
                if($address == 'Pending Order'){ // En caso de ordenes pendientes los pips se los realza en pase su at_price
                    $price_signal=$orden_pendiente;
                }
                
                if($resultado == 1){
                    if( substr( $asset, -3) == 'JPY'  ){
                        $pips = ($stop_loss-$price_signal)*100;
                    }
                    else{
                        $pips = ($stop_loss-$price_signal)*10000;
                    }
                }else{
                    if( substr( $asset, -3) == 'JPY'  ){
                        $pips = ($take_profit-$price_signal)*100;
                    }
                    else{
                        $pips = ($take_profit-$price_signal)*10000;
                    }
                }
                $pips=abs(round($pips,2));
                $table_signals = $wpdb->prefix . "signals";
                
                $precio_actual=generaPriceSignal($cod_price,$tipo_signal,$address); //Obtiene el precio actual de la moneda
                $closing_price_g = $precio_actual;//EL CLOSING PRICE ES ES EL PRECIO QUE VARIA  
                
                //generamos la hora en que llego su fin de la señal
                date_default_timezone_set("Europe/Berlin"); //muestra la fecha y hora de La Paz Bolivia
                $closing_time = date("H:i:s");
                
//                $wpdb->update($table_signals, array('result' => $resultado,'pips' =>$pips), array('ID' => $signal->ID));
                  $wpdb->update($table_signals, array('result' => $resultado,'pips' =>$pips,'closing_price' =>$closing_price_g,'closing_time'=>$closing_time), array('ID' => $signal->ID));
                  
               }
            }
        }
    return $flag;
}
function calculaResultadoSeñal_membership($id,$tipo_signal,$signal,$take_profit,$stop_loss,$price_sell,$price_buy,$orden_pendiente,$cod_op){
    global $wpdb;
    $resultado=0;
    $table_signals_wp= $wpdb->prefix . "signals";
    if ($take_profit != $stop_loss){
        switch ($tipo_signal) {
            case 'Spot':
                            if($signal == 'Sell'){ // preguntamos si la señal es Vender
                                if($price_sell <= $take_profit){
                                    $resultado = 2; //donde 2 es bien
                                }else{
                                    if($price_sell >= $stop_loss){
                                      $resultado = 1; //1 es malo  
                                    }
                                }
                               
                            }else{
                                if($signal == 'Buy'){ //Preguntamos si es Comprar
                                    if($price_buy >= $take_profit){
                                        $resultado = 2; 
                                    }else{
                                        if($price_buy <= $stop_loss){
                                            $resultado = 1; //1 es malo  
                                        }
                                    }    
                                }    
                            }    
                break;
            
            case 'Buy Stop':    
                            if($signal == 'Pending Order'){
                                    if($cod_op == 1){
                                            if ($price_buy >= $take_profit) {
                                                $resultado = 2;
                                            } else {
                                                if ($price_buy <= $stop_loss) {
                                                    $resultado = 1; //1 es malo  
                                                }
                                            }
                                    }else{
                                        if($orden_pendiente <= $price_buy ){
                                            if($id != null){
                                                $wpdb->update($table_signals_wp, array('cod_op' => 1), array('ID' => $id));
                                            }
                                        }
                                    }
                            }
                break;

            case 'Buy Limit':
                            if($signal == 'Pending Order'){
                                    if($cod_op == 1){
                                            if ($price_buy >= $take_profit) {
                                                $resultado = 2;
                                            } else {
                                                if ($price_buy <= $stop_loss) {
                                                    $resultado = 1; //1 es malo  
                                                }
                                            }
                                    }else{
                                        if($orden_pendiente >= $price_buy ){
                                            if($id != null){
                                                $wpdb->update($table_signals_wp, array('cod_op' => 1), array('ID' => $id));
                                            }
                                        }
                                    }
                            }
            break;
            
            case 'Sell Limit':
                            if($signal == 'Pending Order'){
                                    if($cod_op == 1){
                                            if ($price_sell <= $take_profit) {
                                                $resultado = 2;
                                            } else {
                                                if ($price_sell >= $stop_loss) {
                                                    $resultado = 1; //1 es malo  
                                                }
                                            }
                                    }else{
                                        if($orden_pendiente <= $price_sell ){
                                            if($id != null){
                                                $wpdb->update($table_signals_wp, array('cod_op' => 1), array('ID' => $id));
                                            }
                                        }
                                    }
                            }
            break;


            case 'Sell Stop':
                            
                            if($signal == 'Pending Order'){
                                    if($cod_op == 1){
                                            if ($price_sell <= $take_profit) {
                                                $resultado = 2;
                                            } else {
                                                if ($price_sell >= $stop_loss) {
                                                    $resultado = 1; //1 es malo  
                                                }
                                            }
                                    }else{
                                        if($orden_pendiente >= $price_sell ){
                                            if($id != null){
                                                $wpdb->update($table_signals_wp, array('cod_op' => 1), array('ID' => $id));
                                            }
                                        }
                                    }
                            }
            break;

            default:
                break;
        }
    }
    
    return $resultado;
    
}    

//Funcion que muestra los ultimos 3 digitos en color azul
function digitos($precio,$asset){
  $precio_total=$precio;  
    switch ($asset) {
            case 6:
            case 7:
            case 9:
            case 12:
                    $precio2 = substr($precio, -2); 
                    $precio1 = substr($precio,0, -2); 
                    $precio_total = $precio1."<span style='color:#0676F2'>".$precio2."</span>";       
            break;
           
            default:
                    $precio2 = substr($precio, -3); 
                    $precio1 = substr($precio,0, -3); 
                    $precio_total = $precio1."<span style='color:#0676F2'>".$precio2."</span>";
            break;
        }
    
    
    return $precio_total;
}
function muestraRegistro(){
    global $wpdb;

$data = $wpdb->get_results( 
                "SELECT nro_registros
                 FROM ".$wpdb->prefix ."signals_settings"); 
return $data;
}

function getNroSignals($view){
    global $wpdb;
    $nro = $wpdb->get_row( "SELECT nro_registros FROM ".$wpdb->prefix ."signals_settings WHERE vista='{$view}'" );
    return $nro;
}
/***********************************************/
/***********************************************/
/***********************************************/
 ?>