<!-- ========== IN?CIO MENU ========== --> 
<script language="javascript" type="text/javascript" src="<?php echo $this->baseUrl(); ?>/public/scripts/quickmenu.js"></script> 
<div id="menu"> 
 
    <!-- inￜcio: conteￜdo principal #container --> 
    <div id="container"> 
 
        <!-- inￜcio: navegaￜￜo local  --> 
        <script type="text/javascript"> 
            function layout_fluido() {
                var janela = $(window).width(); 
                var fluidNavGlobal = janela - 245; 
                var fluidConteudo = janela - 253; 
                var fluidTitulo = janela - 252; 
                var fluidRodape = janela - 19; 
                $("#navglobal").css("width",fluidNavGlobal); 
                $("#conteudo").css("width",fluidConteudo); 
                $("#titulo").css("width",fluidTitulo); 
                $("#rodapeConteudo").css("width",fluidConteudo); 
                $("#rodape").css("width",fluidRodape); 
                $("div#rodapeConteudo").attr("id", "rodapeConteudo_com_menu"); 
            } 
        </script> 
 
        <style type="text/css"> 
            .sanfonaDiv { 
                clear: both; 
                display: none; 
            } 
        </style> 
        <div id="corfirma" title="Confirmacao" style='display:none;'></div> 
        <div id="ok" title="Confirmacao" style='display:none;'></div>      
    </div> 
</div> 
<!-- ========== FIM MENU ========== -->

