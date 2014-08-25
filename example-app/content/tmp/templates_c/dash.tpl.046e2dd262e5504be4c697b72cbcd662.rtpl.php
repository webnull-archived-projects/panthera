<?php if(!class_exists('Rain\Tpl')){exit;}?><script>$('.ajax_link').click(function (event) { event.preventDefault(); navigateTo(jQuery(this).attr('href')); return false;});</script>
<div class="titlebar"><?php echo localize('Dash'); ?> - <?php echo localize('Everything is here', 'dash'); ?><?php require $this->checkTemplate("/home/panthera/public_html/lib/templates/admin/templates/_navigation_panel.tpl");?></div>
        <?php $counter1=-1;  if( isset($dash_messages) && ( is_array($dash_messages) || $dash_messages instanceof Traversable ) && sizeof($dash_messages) ) foreach( $dash_messages as $key1 => $value1 ){ $counter1++; ?>

            <?php if( $value1["type"] == 'warning' ){ ?>

                <div class="msgWarning" style="display: block;"><?php echo $value1["message"]; ?></div>
            <?php } ?>


            <?php if( $value1["type"] == 'error' ){ ?>

                <div class="msgError" style="display: block;"><?php echo $value1["message"]; ?></div>
            <?php } ?>


            <?php if( $value1["type"] == 'info' ){ ?>

                <div class="msgInfo" style="display: block;"><?php echo $value1["message"]; ?></div>
            <?php } ?>


            <?php if( $value1["type"] == 'success' ){ ?>

                <div class="msgSuccess" style="display: block;"><?php echo $value1["message"]; ?></div>
            <?php } ?>

        <?php } ?>


        <div class="grid-1">
               <ul class="picturesBox">
                   <?php $counter1=-1;  if( isset($dash_menu) && ( is_array($dash_menu) || $dash_menu instanceof Traversable ) && sizeof($dash_menu) ) foreach( $dash_menu as $key1 => $value1 ){ $counter1++; ?>

            	   <li><a style="cursor: pointer;" class="<?php if( $value1["linkType"] == 'ajax' ){ ?>ajax_link<?php } ?>"<?php if( $value1["linkType"] == 'onclick' ){ ?> onclick="<?php echo $value1["link"]; ?>"<?php }else{ ?> href="<?php echo pantheraUrl($value1["link"]); ?>"<?php } ?>>  <img src="<?php echo pantheraUrl($value1["icon"]); ?>" alt=""></a>
            	         <ul class="picturesBoxItem">
                         		<a style="cursor: pointer;" class="<?php if( $value1["linkType"] == 'ajax' ){ ?>ajax_link<?php } ?>"<?php if( $value1["linkType"] == 'onclick' ){ ?> onclick="<?php echo $value1["link"]; ?>"<?php }else{ ?> href="<?php echo pantheraUrl($value1["link"]); ?>"<?php } ?>><?php echo $value1["name"]; ?></a>
                         </ul>
                   </li>
                   <?php } ?>

				</ul>
				 <div class="clear"></div>
        </div>

        <?php if( isset($galleryItems) and count($galleryItems) > 0 ){ ?>

        <div class="grid-2">
           <div class="title-grid"><?php echo localize('Gallery'); ?><span></span></div>
           <div class="content-gird">
           <ul class="picturesBox">
                   <?php $counter1=-1;  if( isset($galleryItems) && ( is_array($galleryItems) || $galleryItems instanceof Traversable ) && sizeof($galleryItems) ) foreach( $galleryItems as $key1 => $value1 ){ $counter1++; ?>

            	   <li><a href="<?php echo pantheraUrl($value1->link); ?>">  <img src="<?php echo pantheraUrl($value1->thumbnail); ?>" alt="" style="max-width: 110px;"></a>
            	         <ul class="picturesBoxItem">
                         		<a href="<?php echo pantheraUrl($value1->link); ?>"><?php echo $value1->title; ?></a>
                         </ul>
                   </li>
                   <?php } ?>

		   </ul>
                <div class="clear"></div>
           </div>
        </div>
        <?php } ?>

        
        <?php if( isset($lastLogged) and count($lastLogged) > 0 ){ ?>

        <div class="grid-2">
           <div class="title-grid"><?php echo localize('Recently logged in users'); ?><span></span></div>
           <div class="content-table-grid">
              <table class="insideGridTable">
                   <?php $counter1=-1;  if( isset($lastLogged) && ( is_array($lastLogged) || $lastLogged instanceof Traversable ) && sizeof($lastLogged) ) foreach( $lastLogged as $key1 => $value1 ){ $counter1++; ?>

                   <tr>
            	        <td><a href="?display=settings&action=my_account&uid=<?php echo $value1["uid"]; ?>" class="ajax_link"><img src="<?php echo $value1["avatar"]; ?>" style="width: 20px"></a></td><td><a href="?display=settings&action=my_account&uid=<?php echo $value1["uid"]; ?>" class="ajax_link"><?php echo $value1["login"]; ?></a></td><td> <?php echo $value1["time"]; ?> <?php echo localize('ago'); ?></td>
            	   </tr>
                   <?php } ?>

               </table>
                <div class="clear"></div>
           </div>
        </div>
        <?php } ?>

