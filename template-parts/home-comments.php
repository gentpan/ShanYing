<?php
if(!defined('ABSPATH'))exit;
$capsules=feng_home_comment_capsules();
if($capsules==='')return;
$many=substr_count($capsules,'class="feng-home-comment"')>=4;
?>
<section class="feng-home-comments<?php echo $many?'':' is-static'; ?>" data-home-comments aria-label="最近的留言">
 <div class="feng-home-comments__viewport">
  <div class="feng-home-comments__track" data-home-comments-track>
   <div class="feng-home-comments__group"><?php echo $capsules; ?></div>
   <?php if($many): ?><div class="feng-home-comments__group" aria-hidden="true" inert><?php echo $capsules; ?></div><?php endif; ?>
  </div>
 </div>
</section>
