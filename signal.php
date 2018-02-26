<?php
/*
Plugin Name: Signals
Plugin URI:  https://google.com
Description: Envia señales de modenas
Version:     1.0
Author:      Cess Rojas
Author URI:  cesarch676@gmail.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

require_once dirname( __FILE__ ) .'/function.php';


function signal_menu(){
	add_menu_page(
		'Signals', //titulo pagina
		'Signals', //titulo menu
//		'read',// aquienes va a mostrar
		'manage_options',// mostrar solo al administrador
		'wp_signal',
		'signal_page',//funcion
		'dashicons-chart-line', //icono del menu
		3
	);
}

add_action('admin_menu','signal_menu');

//Funcion que va a crear las tablas que se necesitan para el plugin
function signal_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "signals";
    $table_name2 = $wpdb->prefix . "signals_price";
    $table_name3 = $wpdb->prefix . "signals_historical";
    $table_name4 = $wpdb->prefix . "signals_settings";
 
    $sql = "CREATE TABLE $table_name (
	`ID` INT(11) NOT NULL AUTO_INCREMENT,
	`date` DATE NULL DEFAULT NULL,
	`time` TIME NULL DEFAULT NULL,
	`address` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
	`type_of_order` VARCHAR(255) NULL DEFAULT '0' COLLATE 'utf8_spanish2_ci',
	`cod_entry_price` INT(11) NULL DEFAULT '0',
	`stop_loss` FLOAT NULL DEFAULT '0',
	`take_profit` FLOAT NULL DEFAULT '0',
        `stop_loss_edit` FLOAT NULL DEFAULT '0',
        `take_profit_edit` FLOAT NULL DEFAULT '0',
	`orden_pendiente` FLOAT NULL DEFAULT '0',
	`quality` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
	`result` CHAR(50) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
        `cancel` TINYINT(1) NOT NULL DEFAULT '0',
        `price_signal` FLOAT NULL DEFAULT '0',
        `pips` FLOAT NULL DEFAULT '0',
        `cod_op` TINYINT(1) NOT NULL DEFAULT '0',
        `beep` TINYINT(1) NOT NULL DEFAULT '0',
        `closing_price` TIME NULL DEFAULT NULL,
        `closing_time` TIME NULL DEFAULT NULL,
        `method` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
        `method_link` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
        `rr_link` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
        `image` BLOB,
        `commentary` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
        `switch_sl` TINYINT(1) NULL DEFAULT '0',
        `switch_tp` TINYINT(1) NULL DEFAULT '0',
        `updated` TINYINT(1) NULL DEFAULT '0',
	PRIMARY KEY (`ID`)
        );";
    
    $sql2 = "CREATE TABLE $table_name2 (
            `cod_entry_price` INT(11) NOT NULL AUTO_INCREMENT,
            `asset` CHAR(50) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
            `price_sell` FLOAT NULL DEFAULT '0',
            `price_buy` FLOAT NULL DEFAULT '0',
            `date_price` DATE NULL DEFAULT NULL,
	    `time_price` TIME NULL DEFAULT NULL,
            `up_down` CHAR(50) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
            PRIMARY KEY (`cod_entry_price`)
            )";
    
    //Se crea nueva tabla para guardar señales historicas
    $sql3 = "CREATE TABLE $table_name3 (
	`id_historical` INT(11) NOT NULL AUTO_INCREMENT,
	`date_h` DATE NULL DEFAULT NULL,
	`time_h` TIME NULL DEFAULT NULL,
	`address_h` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
	`type_of_order_h` VARCHAR(255) NULL DEFAULT '0' COLLATE 'utf8_spanish2_ci',
	`cod_entry_price_h` INT(11) NULL DEFAULT '0',
	`stop_loss_h` FLOAT NULL DEFAULT '0',
	`take_profit_h` FLOAT NULL DEFAULT '0',
	`orden_pendiente_h` FLOAT NULL DEFAULT '0',
	`quality_h` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
	`result_h` CHAR(50) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
        `price_signal_h` FLOAT NULL DEFAULT '0',
        `pips_h` FLOAT NULL DEFAULT '0',
	PRIMARY KEY (`id_historical`)
        );";
    
    $sql4 = "CREATE TABLE $table_name4 (
            `cod_settings` INT(11) NOT NULL AUTO_INCREMENT,
            `nro_registros` INT(11) NULL DEFAULT '20',
            `vista` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8_spanish2_ci',
            PRIMARY KEY (`cod_settings`)
            )";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql2);
    dbDelta($sql3);
    dbDelta($sql4);
    
    cargaDatosIniciales(); //genera datos por defecto a la tabla wp_signals_price
    cargaDatosSettings(); //carga datos para el numero de señales a ver en las vista home y membership
}

function cargaDatosSettings(){
    global $wpdb;
    $table_name = $wpdb->prefix . "signals_settings";
    $arrayDatos1 =array('nro_registros'=>20,'vista'=>'home');
    $arrayDatos2=array('nro_registros'=>20,'vista'=>'membership');
    $wpdb->insert($table_name,$arrayDatos1);
    $wpdb->insert($table_name,$arrayDatos2);
    
}
//por defecto creamos datos iniciales en la tabla wp_signals_price
function cargaDatosIniciales()
{
    global $wpdb;
    $ch = curl_init('https://forex.1forge.com/1.0.3/quotes?pairs=EURUSD,GBPUSD,AUDUSD,NZDUSD,USDCAD,USDJPY,EURJPY,EURAUD,GBPJPY,GBPAUD,AUDNZD,AUDJPY&api_key=T6xtYb1asOJv3dktmqCYGFbckRMP8Ugm');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($ch);
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
        $arrayDatos = array('asset' => $asset, 'price_sell' => $modena_venta,'price_buy' => $modena_compra,'date_price' =>$fecha,'time_price'=>$hora,'up_down'=>"up");
        $wpdb->insert($table_signals_price,$arrayDatos);
    }
	/*global $wpdb;
        $table_name = $wpdb->prefix . "signals_price";
        
        $url_precios_update = "http://65.181.127.143/ss/q.php?symbols=EURUSD,GBPUSD,AUDUSD,NZDUSD,USDCAD,USDJPY,EURJPY,EURAUD,GBPJPY,GBPAUD,AUDNZD,AUDJPY";
        $cambioJSON = file_get_contents($url_precios_update);
        $cambios = json_decode($cambioJSON);

        foreach ($cambios as $key => $value) {
            if ($key <= 11) {
                $datos = explode(" ", $value);

                $updown = $datos[0];
                $asset = $datos[1];
                $modena_venta = $datos[2];
                $modena_compra = $datos[3];
                $fecha = convierteFormatoFecha($datos[4]);
                $hora = $datos[5];
                $arrayDatos = array('asset' => $asset, 'price_sell' => $modena_venta,'price_buy' => $modena_compra,'date_price' =>$fecha,'time_price'=>$hora,'up_down'=>$updown);
                $wpdb->insert($table_name,$arrayDatos);
            }
        }*/

}

//Funcion que convierte el formato de la fecha yy-mm-dd
function convierteFormatoFecha($fecha){
    $fecha = explode(".", $fecha);
    $nuevo_formato_fecha=$fecha[0]."-".$fecha[1]."-".$fecha[2];
    return $nuevo_formato_fecha;
}

register_activation_hook(__FILE__,'signal_install');

//Funcion que desinstala el plugin borra las tabla que se creo al instalar el plugin
function signal_uninstall()
{
	global $wpdb; 
	$table_name = $wpdb->prefix . "signals";
	$sql = "DROP TABLE $table_name";
	$wpdb->query($sql);
        
        $table_name2 = $wpdb->prefix . "signals_price";
	$sql2 = "DROP TABLE $table_name2";
	$wpdb->query($sql2);
        
        $table_name3 = $wpdb->prefix . "signals_historical";
	$sql3 = "DROP TABLE $table_name3";
	$wpdb->query($sql3);
        
        $table_name4 = $wpdb->prefix . "signals_settings";
	$sql4 = "DROP TABLE $table_name4";
	$wpdb->query($sql4);
        
        
}
register_deactivation_hook(__FILE__,'signal_uninstall');



//crea la pagina del adminsitrador para que pueda ingresar datos
function signal_page(){ //define el contenido de una pagina

//actualizaPrecios();
    
    $signal_price = muestraMonedas();
  ?>
<div id="divLoading"></div>
<div class="wrap">
    <h1>Signal Interface</h1>
                  
                        
    <div class="container-fluid">
	<div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-3">
                        <!-- Nro de registros  -->
                        <?php 
                           $nro=muestraRegistro();
                           $nro_home=$nro[0]->nro_registros;
                           $nro_membership=$nro[1]->nro_registros;
                        ?>
                        <div class="row row_registros"><label>Nro. Home</label><input class="form-control nro_registro" id="nro_home"  type="number"  step=1 value="<?php echo $nro_home; ?>" /></div>
                        <div class="row row_registros"><label>Nro. Membership</label><input class="form-control nro_registro" id="nro_membership"  type="number"  step=1 value="<?php echo $nro_membership; ?>"  /></div>     
                        <div class="row row_registros"><button type="button" class="btn btn-success" id="btn-registros" style="margin-left: 38%">Save</button> </div>     
                    </div>
                    <div class="col-md-4 border-form" style="width: 34.333%;">
                        <form role="form">
                            <div class="form-group">
                                <label class="control-label">Símbolo:</label>
                                <select class="form-control" id="select-simbolo">
                                    <?php if (count($signal_price) > 0) : ?>
                                       <?php foreach ($signal_price as $value): ?> 
                                              <!--<option label_sell="<?php // echo $value->price_sell; ?>" label_buy="<?php echo $value->price_buy; ?>" label_date="<?php echo $value->date_price; ?>" label_time="<?php echo $value->time_price; ?>" value="<?php echo $value->cod_entry_price; ?>" ><?php echo formatoCombroPrecios($value->asset);?></option>-->
                                              <option value="<?php echo $value->cod_entry_price; ?>" ><?php echo formatoCombroPrecios($value->asset);?></option>
                                        <?php endforeach; ?> 
                                    <?php endif; ?>
                                </select>
                            </div>
<!--                                        <div class="form-group"> 
                                    <p><b>Sell:</b> <span id="sell_precio"></span>     <b>Buy:</b> <span id="buy_precio"></span>     <b>Date:</b> <span id="date_precio"></span> <b>Time:</b> <span id="time_precio"></span></p>  
                            </div>-->
                            <div class="form-group">
                                <label class="control-label">Calidad:</label>
                                <select class="form-control" id="select-calidad">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>    


                            <div class="form-group row"> <!-- class row ayuda a que los 2 input esten en la misma fila -->
                                <div class="col-md-6">
                                    <label for="label-stop-loss">Stop Loss</label>
                                    <input class="form-control" id="stop_loss" type="number"  step=1 />
                                </div>

                                <div class="col-md-6"> 
                                    <label for="label-take-profit">Take profit</label>
                                    <input class="form-control" id="take_profit"  type="number"  step=1  />
                                </div>
                                <span id="error_tp_sl" class="text-error"></span>
                            </div>

<!--                                            <div class="form-group">
                                    <label class="control-label">Comentario:</label>
                                    <input type="text" class="form-control">
                                </div>-->

                            <div class="form-group">
                                <label class="control-label">Tipo:</label>
                                <select class="form-control" id="tipo_signal">
                                    <option value="1">Ejecucion por Mercado</option>
                                    <option value="2">Orden Pendiente</option>
                                </select>
                            </div>    
                            <div class="form-group row"> <!-- class row ayuda a que los 2 input esten en la misma fila -->
                                <div class="col-md-6">
                                    <label for="label-stop-loss">Método</label>
                                    <input class="form-control" id="method_e" type="text" />
                                </div>

                                <div class="col-md-6"> 
                                    <label for="label-take-profit">Link</label>
                                    <input class="form-control" id="method_link_e"  type="text"  />
                                </div>
                                <span id="error_tp_sl" class="text-error"></span>
                            </div>
                            <div class="form-group">
                                <label class="control-label">R/R link</label>
                                <input type="text" class="form-control" id="rr_link_g"/>
                            </div>
                            
                            <div class="form-group" id="orden_pendiente" style="display:none">
                                <label class="control-label">Orden Pendiente:</label>
                                <select class="form-control" id="tipo_orden">
                                    <option value="Buy Limit">Buy Limit</option>
                                    <option value="Sell Limit">Sell Limit</option>
                                    <option value="Buy Stop">Buy Stop</option>
                                    <option value="Sell Stop">Sell Stop</option>
                                </select>
                            </div>

                            <div id="at_price_principal" class="form-group" style="display:none">
                                <div class="col-md-6">
                                <label for="exampleInputPassword1">At price</label>
                                <input class="form-control" id="at_price" type="number"  step=1 />
                                </div>
                                <!--<div class="col-md-6" style="margin-top: -36px;">-->
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-primary btn-md btn-at-price btn-signal-price" id="btn-at_price" style="margin-top: -36px;">
                                        Place
                                    </button> 
                                </div>    
                            </div>       
                            <div class="clearfix"></div>

                            <button type="button" class="btn btn-primary btn-signal" id="btn-sell">
                                Vender al Mercado
                            </button> 
                            <button type="button" class="btn btn-danger btn-signal" id="btn-buy">
                                Comprar al Mercado
                            </button>
                        </form>
                    </div>
                    
                    <!-- ***********************************  BEGIN FORMULARIO PUBLICACION  ************************************  -->
                    
                    
                    <div class="col-md-4 border-form" style="width: 34.333%;">
                        <form role="form">
                            <div class="form-group row"> <!-- class row ayuda a que los 2 input esten en la misma fila -->
                                <div class="col-md-4" style="display: none;">
                                    <label for="label-stop-loss">ID SIGNAL</label>
                                    <input class="form-control" id="id-signal-publi" type="text" style="height:2.5em;"/>
                                </div>
                                <div class="col-md-3">
                                    <label for="label-stop-loss">N°</label>
                                    <input class="form-control" id="num-signal-publi" type="text" disabled style="height:2.5em;"/>
                                </div>
                                <div class="col-md-5">
                                    <label for="label-stop-loss">¿Que publicar?</label>
                                    <select class="form-control" id="select_publi">
                                        <option value="1" selected>Comentario</option>
                                        <option value="2">Imagen</option>
                                    </select>
                                </div>
                                <div class="col-md-7"> 
                                    <label for="label-take-profit" style="visibility:hidden;display: block;">Link</label>
                                    <label class="btn btn-warning btn-signal" for="btn-imagen" id="label-imagen">Subir imagen</label>
                                    <input class="form-control" id="btn-imagen"  type="file" style="display:none;" />
                                </div>
                            </div>
                            <div class="form-group row" id="img_vista">    
                                <div class="col-md-12" >
                                    <label id="nom-arch"></label>
                                    <img id="image_g" style="max-width:100%; display:block;"/>
                                </div>
                                
                                <span id="error_tp_sl" class="text-error"></span>
                            </div>
                            <div class="form-group row" id="coment_publi">    
                                <div class="col-md-12" > 
                                    <label for="label-take-profit">Comentario</label>
                                    <textarea rows="7" maxlength="240" class="form-control" id="publi-comentario" style="resize:none;"></textarea>
                                </div>
                                
                                <span id="error_tp_sl" class="text-error"></span>
                            </div>
                            <div class="form-group row">    
                                <div class="col-md-10">
                                    <label for="label-take-profit" style="visibility:hidden;">Link</label>
                                    <button type="button" class="btn btn-primary " id="btn-update-publi" >
                                        Actualizar publicación
                                    </button> 
                                </div>
                            </div>
                            <div class="form-group row" id="img_vista_php">    
                                <div class="col-md-12" >
                                    <label id="nom-arch"></label>
                                    <img id="image_g_php" style="max-width:100%; display:block;"/>
                                </div>
                                
                                <span id="error_tp_sl" class="text-error"></span>
                            </div>
                            
                        </form>
                    </div>
                </div>
                
                
                <!-- ***********************************  BEGIN FORMULARIO UPDATE  ************************************  -->
                
                
                <div class="row" >
                    <div class="col-md-3"></div>
                    <div class="col-md-4 border-form" style="width: 34.333%;padding-bottom: 5px;">
                        <form role="form">
                            <div class="form-group row" > <!-- class row ayuda a que los 2 input esten en la misma fila -->
                                <div class="col-md-4" style="display: none;">
                                    <label for="label-stop-loss">ID SIGNAL</label>
                                    <input class="form-control" id="id-signal" type="text" style="height:2.5em;"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="label-stop-loss">N°</label>
                                    <input class="form-control" id="num-signal" type="text" disabled style="height:2.5em;"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="label-stop-loss">New Stop Loss</label>
                                    <input class="form-control" id="stop-loss-edit" type="number"  step=1 style="height:2.5em;"/>
                                </div>

                                <div class="col-md-4"> 
                                    <label for="label-take-profit">New Take profit</label>
                                    <input class="form-control" id="take-profit-edit"  type="number"  step=1  style="height:2.5em;"/>
                                </div>
                                <!--<div class="col-md-2">
                                    <label for="label-take-profit" style="display: block;">Imagen</label>
                                    <label class="btn btn-warning btn-signal" for="publi-imagen">Subir imagen</label>
                                    <input class="form-control" id="publi-imagen"  type="file" style="display:none;" />
                                </div>
                                
                                <div class="col-md-2"> 
                                    <label for="label-take-profit">Commentary</label>
                                    <input class="form-control" id="Commentary"  type="text"  />
                                </div>
                                <div class="col-md-1" >
                                    <label for="label-stop-loss">To post</label>
                                    <input class="form-control" id="cbx-post" type="checkbox" />
                                </div>-->
                            </div>
                            <div class="form-group row" >    
                                <div class="col-md-6">
                                    <label for="label-take-profit" style="visibility:hidden;">Link</label>
                                    <button type="button" class="btn btn-primary " id="btn-update" >
                                        Update
                                    </button> 
                                </div>
                               
                                <div class="col-md-6">
                                    <label for="label-take-profit" style="visibility:hidden;">Link</label>
                                    <button type="button" class="btn btn-primary " id="btn-cancelar" >
                                        Cancelar
                                    </button> 
                                </div>
                                
                                <span id="error_tp_sl" class="text-error"></span>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-1">
                    </div>
                </div>
            </div>
    	</div>
    </div>           
                     <br>
                        <!--<div id="tabla-signal-admin"></div>     muestra toda la tabla de signal-->
                        <?php echo do_shortcode('[signals view="admin"]'); //muestra la tabla ?>


</div>
  <?php
}
