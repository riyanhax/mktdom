jQuery(document).ready(function() {
   validar(); //Funcion que valida los input stop loss, take profit y at price 
   validarNroRegistros();
    /* Funcion que va a refrescar la tabla cada segundo */
//    setInterval(signalTable,300000); //actualizamos esta funcion cada hora
    /****************************/
    signalTable();
//    setInterval(signalTable,30000);
//    
   function signalTable() {
        var date = new Date();
        
        var view_table = jQuery('.view_table').val();  //obtenemos la vista de la tabla
        
                switch (view_table) {
                
                    case 'admin':
                                jQuery.post('/wp-admin/admin-ajax.php', {
                                    action: 'refresh',
                                }, function(data, status) { 
                                        if(status == 'success'){
                                            var obj = JSON.parse(data);
                                            jQuery( ".table-signal" ).html(obj ); //insertamos toda la tabla al shortcode
                                            console.log("Hora---->"+date.getHours()+":"+date.getMinutes()+":"+date.getSeconds());

                                            if (date.getHours() >= 22 && date.getMinutes() >= 0 && date.getSeconds() >= 0) {
                                                if (date.getHours() == 22 && date.getMinutes() == 0 && date.getSeconds() < 2) {
                                                    closeMarket();
                                                    signalTable();
                                                }
                                            } else {
                                                if (date.getHours() < 22 && date.getHours() > 2) {
                                                    signalTable(); //sirve para que cuando termine de ejecutar esta funcion se llame asi misma para hacer otra vez
                                                }
                                            }

                                        }
                                    }
                                );
                    break;
                    
                    case 'membership':
                             jQuery.post('/wp-admin/admin-ajax.php', {
                                    action: 'refresh_membership',
                                }, function(data, status) { 
                                        if(status == 'success'){
                                            var obj = JSON.parse(data);
                                            jQuery( ".table-signal" ).html(obj ); //insertamos toda la tabla al shortcode

                                             if (date.getHours() >= 22 && date.getMinutes() >= 0 && date.getSeconds() >= 0) {
                                                if (date.getHours() == 22 && date.getMinutes() == 0 && date.getSeconds() < 2) {
                                                    closeMarket();
                                                    signalTable();
                                                }
                                            } else {
                                                if (date.getHours() < 22 && date.getHours() > 2) {
                                                    signalTable(); //sirve para que cuando termine de ejecutar esta funcion se llame asi misma para hacer otra vez
                                                }
                                            }

                                        }
                                    }
                                );
                    break;
                    
                    case 'home':
                    default:
                            jQuery.post('/wp-admin/admin-ajax.php', {
                                    action: 'refresh_home',
                                }, function(data, status) { 
                                        if(status == 'success'){
                                            var obj = JSON.parse(data);
                                            jQuery( ".table-signal" ).html(obj ); //insertamos toda la tabla al shortcode
                                        }
                                    }
                                );
                    break;
                } 
    }
    
   
    function actualizaPrecios() {
        jQuery.post('/wp-admin/admin-ajax.php', {
                action: 'update_precios',
            }, function(data, status) { 
                    if(status == 'success'){
                        
                    }
                }
            );
    }


    function closeMarket() {
        jQuery.post('/wp-admin/admin-ajax.php', {
                action: 'close_market',
            }, function(data, status) { 
                    if(status == 'success'){
                        alert("Successful market closure process");
                    }
                }
            );
    }

    
   jQuery('#mensaje-signal').hide(); 
//   muestraPrice(); // muestra un precio por defecto en stop loss y take profit

    
//   jQuery('#table_result').DataTable();
//    jQuery('#table_result').DataTable({
//        "order": [[0, "desc"]]
//    });
    
    jQuery("#select-calidad").change(function () {
        validar();
    });
    
    
    jQuery("#tipo_signal").change(function () {
        validar();
        var tipo = jQuery("#tipo_signal").val();
        if (tipo == 1){
            jQuery("#orden_pendiente").hide();
            jQuery("#btn-sell").show();
            jQuery("#btn-buy").show();
            jQuery("#at_price_principal").hide();
        }else{
            jQuery("#orden_pendiente").show();
            jQuery("#btn-sell").hide();
            jQuery("#btn-buy").hide();
            jQuery("#at_price_principal").show();
        }
    });
    jQuery("#select-simbolo").change(function () {
        validar();
//        muestraPrice(); // muestra un precio por defecto en stop loss y take profit
    });
    
    jQuery("#tipo_orden").change(function () {
        validar();
    });
   
   
    jQuery("#btn-sell").click(function () {
          generaInsert("Sell");
    });
    jQuery("#btn-buy").click(function () {
        generaInsert("Buy");
    });
    
    jQuery("#btn-at_price").click(function () {
        generaInsert("Pending Order");
    });
    
    jQuery("#btn-registros").click(function () {
        var home = jQuery("#nro_home").val();
        var membership = jQuery("#nro_membership").val();
        jQuery.post('/wp-admin/admin-ajax.php', {
                action: 'signal_settings',
                home:home,
                membership:membership
            }, function(data, status) { 
                    if(status == 'success'){
                        alert('Data was successfully registered.');
                        jQuery("#nro_home").val(home);
                        jQuery("#nro_membership").val(membership);
                        validarNroRegistros();
                    }else{
                        alert("Algo salio mal");
                    }
                }
            );
        
    });
   
 /*  // muestra un precio por defecto en stop loss y take profit
   function muestraPrice(){
       var price_signal_sell = jQuery('#select-simbolo :selected').attr('label_sell'); 
       var price_signal_buy = jQuery('#select-simbolo :selected').attr('label_buy'); 
       var date_price = jQuery('#select-simbolo :selected').attr('label_date'); 
       var time_price = jQuery('#select-simbolo :selected').attr('label_time'); 
       
       jQuery("#sell_precio").text(price_signal_sell);
       jQuery("#buy_precio").text(price_signal_buy);
       jQuery("#date_precio").text(date_price);
       jQuery("#time_precio").text(time_price);
//       jQuery("#stop_loss").val(price_signal_sell);
//       jQuery("#take_profit").val(price_signal_buy);
   } */
   
   function generaInsert(signal){
        var orden_pendiente = 0;
        var cod_asset = jQuery("#select-simbolo").val();
        var cod_calidad = jQuery("#select-calidad").val();
        var stop_loss = jQuery("#stop_loss").val();
        var take_profit = jQuery("#take_profit").val();
        var method_e = jQuery("#method_e").val();
        var method_link_e= jQuery("#method_link_e").val();
        var rr_link_g= jQuery("#rr_link_g").val();
        /* Obtenemos precios de compra y venta */
       var price_signal_sell = jQuery('#select-simbolo :selected').attr('label_sell'); 
       var price_signal_buy = jQuery('#select-simbolo :selected').attr('label_buy'); 
       
        if(signal == 'Pending Order'){
            var orden_pendiente = jQuery("#at_price").val();
        }
        
        var tipo_signal = jQuery("#tipo_signal").val();
        
        if(tipo_signal == 1){
            tipo_signal = "Spot";
        }else{
            tipo_signal = jQuery("#tipo_orden").val();    
        }
       
        jQuery("div#divLoading").addClass('show'); //genera gif
        jQuery.post('/wp-admin/admin-ajax.php', {
                action: 'registra',
                cod_asset:cod_asset,
                cod_calidad:cod_calidad,
                stop_loss:stop_loss,
                take_profit:take_profit,
                tipo_signal:tipo_signal,
                signal:signal,
                orden_pendiente:orden_pendiente,
                method_e:method_e,
                method_link_e:method_link_e,
                rr_link_g:rr_link_g
            }, function(data, status) { 
                    if(status == 'success'){
//                        jQuery('#mensaje-signal').show(); 
                        alert('Data was successfully registered.');
                        jQuery("div#divLoading").removeClass('show');//elimina gif 
                        var obj = JSON.parse(data);
                        jQuery( "#tabla-signal-admin" ).html(obj );
//                        jQuery('#table_result').DataTable();
//                        jQuery('#table_result').DataTable({ //ordena la tabla
//                            "order": [[0, "desc"]]
//                        });
                        
                        jQuery('#take_profit').val('');
                        jQuery('#stop_loss').val('');
                        jQuery('#at_price').val('');
                        validar();
                    }else{
                        alert("Algo salio mal");
                    }
                }
            );
       
   }

/* Funciones para validar señales */
    
jQuery(document).on('keyup mouseup', '#stop_loss', function() {        
    validar();
});
jQuery(document).on('keyup mouseup', '#take_profit', function() { 
    validar();
});

jQuery(document).on('keyup mouseup', '#at_price', function() {
    validar();
});

jQuery(document).on('keyup mouseup', '#nro_home', function() {
    validarNroRegistros();
});
jQuery(document).on('keyup mouseup', '#nro_membership', function() {
    validarNroRegistros();
});

function validarNroRegistros(){
    home = jQuery("#nro_home").val();
    membership = jQuery("#nro_membership").val();
    if( home == null || home.length == 0 || /^\s+$/.test(home) || home<=0 || home>20 || membership == null || membership.length == 0 || /^\s+$/.test(membership) || membership <=0 || membership >20 ){
        jQuery('#btn-registros').attr("disabled", true);
        return false;
    }else{
        jQuery('#btn-registros').attr("disabled", false);
        return false;
    }
    
}

function validar(){
    sl = jQuery("#stop_loss").val();
    tp = jQuery("#take_profit").val();
    at = jQuery("#at_price").val();
    tipo_signal = jQuery("#tipo_signal").val();
    
    if(tipo_signal == 1){
            if( sl == null || sl.length == 0 || /^\s+$/.test(sl) || sl<=0 || tp == null || tp.length == 0 || /^\s+$/.test(tp) || tp <=0){
                jQuery('#btn-sell').attr("disabled", true);
                jQuery('#btn-buy').attr("disabled", true);
                return false;
            }else{
                jQuery('#btn-sell').attr("disabled", false);
                jQuery('#btn-buy').attr("disabled", false);
                return false;
            }
    }
   if(tipo_signal == 2){
        if(at == null || at.length == 0 || /^\s+$/.test(at) || at <=0 || sl == null || sl.length == 0 || /^\s+$/.test(sl) || sl<=0 || tp == null || tp.length == 0 || /^\s+$/.test(tp) || tp <=0){
            jQuery('#btn-at_price').attr("disabled", true);
            return false;
        }else{
            jQuery('#btn-at_price').attr("disabled", false);
            return false;
        }
   }
}

}); //end JQuery




var signal = {
    delete: function(id_signal) {
        jQuery("div#divLoading").addClass('show'); //genera gif
        if (id_signal.length > 0) {
            jQuery.post('/wp-admin/admin-ajax.php', {
                action: 'elimina',
                id_signal:id_signal
            }, function(data, status) { 
                    if(status == 'success'){
                        alert("Successfully deleted");
                        jQuery("div#divLoading").removeClass('show');//elimina gif 
                        var obj = JSON.parse(data);
                        jQuery( "#tabla-signal-admin" ).html(obj );
//                        jQuery('#table_result').DataTable();
//                        jQuery('#table_result').DataTable({ //ordena la tabla
//                            "order": [[0, "desc"]]
//                        });
                    }
                }
            );



        }
    }  
};
var signal = {
    cancel: function(id_signal,valor,cod_price,tipo_signal,signal,stop_loss,take_profit,price_signal,orden_pendiente,cod_op) {

        jQuery("div#divLoading").addClass('show'); //genera gif
        if (id_signal.length > 0) {
            jQuery.post('/wp-admin/admin-ajax.php', {
                action: 'cancelar',
                id_signal:id_signal,
                valor:valor,
                cod_price:cod_price,
                tipo_signal:tipo_signal,
                signal:signal,
                stop_loss:stop_loss,
                take_profit:take_profit,
                price_signal:price_signal,
                orden_pendiente:orden_pendiente,
                cod_op:cod_op,
            }, function(data, status) { 
                    if(status == 'success'){
                        alert("Successfully cancel");
                        jQuery("div#divLoading").removeClass('show');//elimina gif 
                        var obj = JSON.parse(data);
                        jQuery( "#tabla-signal-admin" ).html(obj );
                    }
                }
            );



        }
    }  
};
function actualizarTakeProfit_StopLoss(id_signal){
    var stop_loss_edit = jQuery("#stop-loss-edit").val();
    var take_profit_edit = jQuery("#take-profit-edit").val();
    if (id_signal.length > 0) {
        jQuery.post('/wp-admin/admin-ajax.php', {
            action: 'actualiza',
            id_signal:id_signal,
            stop_loss_edit:stop_loss_edit,
            take_profit_edit:take_profit_edit,
        }, function(data, status) { 
                if(status == 'success'){
                    alert("Successfully update");
                    alert(stop_loss_edit+'        '+take_profit_edit);
                    jQuery("div#divLoading").removeClass('show');//elimina gif 
                    var obj = JSON.parse(data);
                    jQuery( "#tabla-signal-admin" ).html(obj );
                }
            }
        );
    }
    
}