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
	'content' => '',
	'description' => '',
	'image_size' => '',
	'product_url' => '',
	'thumbnail_url' => '',	
	'author' => '',
	'ASIN' => '',
	'date' => '',
	'is_adult_product' 	=> '',
	'price' => '',
	'lowest_new_price' => '', 
	'lowest_used_price' => '',
); 
?>
<?php if ( ! isset( $arrProducts ) || empty( $arrProducts ) ) : ?>
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
		<h4 class="amazon-product-title">
			<a href="<?php echo $arrProduct['product_url']; ?>" title="<?php echo $arrProduct['text_description']; ?>" target="_blank" rel="nofollow">
				<?php echo $arrProduct['title']; ?>
			</a>
		</h4>
		<div class="amazon-product-thumbnail-container">
			<div class="amazon-product-thumbnail" style="max-width:<?php echo $arrArgs['image_size']; ?>px;">
				<a href="<?php echo $arrProduct['product_url']; ?>" title="<?php echo $arrProduct['text_description']; ?>" target="_blank" rel="nofollow">
					<img src="<?php echo $arrProduct['thumbnail_url']; ?>" alt="<?php echo $arrProduct['text_description']; ?>" />
				</a>
			</div>
		</div>
		<?php if ( $arrProduct['author'] ) : ?>
		<span class="amazon-product-author">
			<?php echo sprintf( __( 'by %1$s', 'amazon-auto-links' ), $arrProduct['author'] ); ?>
		</span>
		<?php endif; ?>
		<?php if ( $arrProduct['price'] ) : ?>
		<span class="amazon-product-price">
			<?php echo sprintf( __( 'at %1$s', 'amazon-auto-links' ), $arrProduct['price'] ); ?> 
		</span>
		<?php endif; ?>		
		<?php if ( $arrProduct['lowest_new_price'] ) : ?>
		<span class="amazon-product-lowest-new-price">
			<?php echo sprintf( __( 'New from %1$s', 'amazon-auto-links' ), $arrProduct['lowest_new_price'] ); ?> 
		</span>
		<?php endif; ?>			
		<?php if ( $arrProduct['lowest_used_price'] ) : ?>
		<span class="amazon-product-lowest-used-price">
			<?php echo sprintf( __( 'Used from %1$s', 'amazon-auto-links' ), $arrProduct['lowest_used_price'] ); ?> 
		</span>
		<?php endif; ?>					
		<div class="amazon-product-description">
			<?php echo $arrProduct['description']; ?>
		</div>
	</div>
<?php endforeach; ?>	
</div>