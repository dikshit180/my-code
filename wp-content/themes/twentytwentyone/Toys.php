<?php
/* Template Name: Toy-Page */
get_header();
?>

<div class="wrap">
    <h1>Toys Archive</h1>
    <div id="toy-posts-wrap"></div>
    <div id="toy-pagination"></div>
</div>

<script>
jQuery(document).ready(function ($) {
    function loadToys(page = 1) {
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'load_toy_posts',
                page: page
            },
            beforeSend: function () {
                $('#toy-posts-wrap').html('<p>Loading...</p>');
            },
            success: function (response) {
                $('#toy-posts-wrap').html(response.data.posts);
                $('#toy-pagination').html(response.data.pagination);
            }
        });
    }

    loadToys();

    $(document).on('click', '.toy-page-link', function (e) {
        e.preventDefault();
        let page = $(this).data('page');
        loadToys(page);
    });
});
</script>

<?php get_footer(); ?>
