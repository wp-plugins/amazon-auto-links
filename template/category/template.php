<?php
/*
 * Available variables:
 * 
 * $arrOptions - the plugin options
 * $arrProducts - the fetched product links
 * $arrArgs - the user defined arguments such as image size and count etc.
 */

$arrStructure_Product = array(
	'product_url' => '',
	'title' => '',
	'text_description' => '',
	'description' => '',
	'image_size' => '',
	'product_url' => '',
	'thumbnail_url' => '',	
);
  
	 
?>
<?php if ( empty( $arrProducts ) ) : ?>
	<div><p><?php _e( 'No products found.', 'amazon-auto-links' ); ?></p></div>
	<?php return; ?>
<?php endif; ?>	

<?php if ( isset( $arrProducts['Error']['Message'], $arrProducts['Error']['Code'] ) ) : ?>	
	<div class="error">
		<p>
			<?php echo $arrProducts['Error']['Code'] . ': '. $arrProducts['Error']['Message']; ?>
		</p>
	</div>
<?php return; ?>
<?php endif; ?>
	
<div class="amazon-products-container">
<?php foreach( $arrProducts as $arrProduct ) : ?>
	<?php $arrProduct = $arrProduct + $arrStructure_Product; ?>
	<div class="amazon-product-container">
		<?php echo $arrProduct['formed_item']; ?>
	</div>
<?php endforeach; ?>	
</div>
