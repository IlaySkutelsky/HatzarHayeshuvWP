<?php
if ( ! defined( 'ABSPATH' ) ) {exit;}
?>
<?php  if(!SeipOpcodespace::isPaid()): ?>
<div class="sidebar_box">
	<div class="sidebar_ttl">Pro Features</div>
	<div class="sidebar_box_body">
		<ul>
			<li><span class="li_icon"><span class="dashicons dashicons-saved"></span></span><strong>Pro Fields:</strong> Image, Gallery, File, Link</li>
			<li><span class="li_icon"><span class="dashicons dashicons-saved"></span></span><strong>Flexible Content Layout</strong></li>
			<li><span class="li_icon"><span class="dashicons dashicons-saved"></span></span>Bulk Export/Import</li>
			<li><span class="li_icon"><span class="dashicons dashicons-saved"></span></span>Export/Import ACF Options Data</li>
			<li><span class="li_icon"><span class="dashicons dashicons-saved"></span></span>Taxonomy (Category, Tag, Custom Taxonomy) of Post / Custom Post Type.</li>
		</ul>
		<a href="https://opcodespace.com/product/simple-export-import-pro-for-acf/" class="seip_btn seip_btn-warning" target="_blank">Upgrade Now (only $14 for two sites)</a>
        <br><br>
		<a href="https://opcodespace.com/product/simple-export-import-pro-for-acf-unlimited/" class="seip_btn seip_btn-warning" target="_blank">Upgrade Now (only $49/year for unlimited sites)</a>
        <p><strong>Notes:</strong> License key is not transferable to another domain.</p>
	</div>
</div>
<?php endif ?>