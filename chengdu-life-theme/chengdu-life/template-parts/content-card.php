<?php
/**
 * 文章卡片局部模板
 * @package ChengduLife
 */
$cat = cdl_get_primary_category();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'post-card' ); ?>>
    <?php if ( has_post_thumbnail() ) : ?>
    <a href="<?php the_permalink(); ?>" class="post-card-thumb-link">
        <?php the_post_thumbnail( 'cdl-card', [ 'class' => 'post-card-thumb' ] ); ?>
    </a>
    <?php endif; ?>
    <div class="post-card-body">
        <?php if ( $cat ) : ?>
        <a href="<?php echo esc_url( get_category_link( $cat ) ); ?>"
           class="tag <?php echo esc_attr( cdl_get_category_class( $cat->slug ) ); ?>">
            <?php echo esc_html( $cat->name ); ?>
        </a>
        <?php endif; ?>
        <h3 class="post-card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>
        <div class="post-card-meta">
            <span><i class="far fa-clock"></i> <?php echo cdl_human_time_diff(); ?></span>
            <span><i class="far fa-eye"></i> <?php echo cdl_reading_time(); ?></span>
        </div>
    </div>
</article>
