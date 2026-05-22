<?php
/*
Plugin Name: Event Plugin
Description: Event Management Plugin
Version: 1.0
*/

if (!defined('ABSPATH')) {
    exit;
}


/* REGISTER EVENT POST TYPE */

add_action('init', 'register_event_post_type');

function register_event_post_type()
{
    register_post_type(
        'event',
        [
            'labels' => [
                'name' => 'Events',
                'singular_name' => 'Event'
            ],

            'public' => true,

            'has_archive' => false,

            'rewrite' => [
                'slug' => 'events'
            ],

            'supports' => [
                'title',
                'editor',
                'thumbnail'
            ],

            'menu_icon' =>
            'dashicons-calendar-alt',

            'show_in_rest' => true
        ]
    );
}



/* REGISTER TAXONOMY */

add_action(
'init',
'register_event_taxonomy'
);

function register_event_taxonomy()
{

register_taxonomy(

'event_type',

'event',

[

'label'=>'Event Type',

'public'=>true,

'hierarchical'=>true,

'show_in_rest'=>true

]

);

}



/* REGISTER META */

add_action(
'init',
'register_event_meta'
);

function register_event_meta()
{

$fields=[

'event_date',

'event_location',

'event_venue',

'tickets'

];


foreach(
$fields as $field
){

register_post_meta(

'event',

$field,

[

'type'=>'string',

'single'=>true,

'show_in_rest'=>true

]

);

}

}



/* META BOX */

add_action(
'add_meta_boxes',
'add_event_meta_box'
);

function add_event_meta_box()
{

add_meta_box(

'event_info',

'Event Details',

'render_event_meta_box',

'event'

);

}


function render_event_meta_box(
$post
)
{

?>

<p>

<label>
Event Date
</label>

<br>

<input
type="date"
name="event_date"

value="<?php
echo esc_attr(
get_post_meta(
$post->ID,
'event_date',
true
)
);
?>"

>

</p>


<p>

<label>
Location
</label>

<br>

<input
type="text"
name="event_location"

value="<?php
echo esc_attr(
get_post_meta(
$post->ID,
'event_location',
true
)
);
?>"

>

</p>


<p>

<label>
Venue
</label>

<br>

<input
type="text"
name="event_venue"

value="<?php
echo esc_attr(
get_post_meta(
$post->ID,
'event_venue',
true
)
);
?>"

>

</p>


<p>

<label>
Tickets
</label>

<br>

<input
type="number"
name="tickets"

value="<?php
echo esc_attr(
get_post_meta(
$post->ID,
'tickets',
true
)
);
?>"

>

</p>

<?php

}



/* SAVE META */

add_action(
'save_post',
'save_event_meta'
);

function save_event_meta(
$post_id
){

if(
defined(
'DOING_AUTOSAVE'
)
&&
DOING_AUTOSAVE
){
return;
}

if(
!isset(
$_POST['event_meta_nonce']
)
||
!wp_verify_nonce(
$_POST['event_meta_nonce'],
'event_meta_action'
)
){
return;
}

if(
get_post_type(
$post_id
)!=='event'
){
return;
}


$fields=[

'event_date',

'event_location',

'event_venue',

'tickets'

];


foreach(
$fields as $field
){

if(
isset(
$_POST[$field]
)
){

$value=
$_POST[$field];


if(
$field==='tickets'
){

$value=
intval(
$value
);

}else{

$value=
sanitize_text_field(
$value
);

}


update_post_meta(

$post_id,

$field,

$value

);

}

}

}



add_shortcode(
'event_form',
'event_form_shortcode'
);

function event_form_shortcode()
{

ob_start();

?>

<form
id="event-form"
enctype="multipart/form-data"
>

<?php wp_nonce_field('event_form_action', 'event_form_nonce'); ?>

<p>
Event Name
<br>
<input
type="text"
name="event_name"
required
>
</p>


<p>
Event Date
<br>
<input
type="date"
name="event_date"
required
>
</p>


<p>
Location
<br>
<input
type="text"
name="event_location"
required
>
</p>


<p>
Venue
<br>
<input
type="text"
name="event_venue"
required
>
</p>


<p>
Event Type
<br>
<input
type="text"
name="event_type"
required
>
</p>


<p>
Tickets
<br>
<input
type="number"
name="tickets"
required
>
</p>


<p>
Image
<br>
<input
type="file"
name="event_image">
</p>


<button
type="submit"
>

Create Event

</button>

</form>


<div
id="event-message"
></div>

<?php

return
ob_get_clean();

}
add_action(
'wp_footer',
'event_ajax_script'
);

function event_ajax_script()
{

?>

<script>

jQuery(
function($){

$('#event-form')
.submit(
function(e){

e.preventDefault();

var form =
new FormData(
this
);

form.append(
'action',
'create_event'
);

form.append(
'nonce',
'<?php echo wp_create_nonce("create_event_action"); ?>'
);


$.ajax({

url:
'<?php
echo admin_url(
'admin-ajax.php'
);
?>',

type:
'POST',

data:
form,

processData:
false,

contentType:
false,

success:
function(
response
){

$('#event-message')
.html(
response.data
);

$('#event-form')[0]
.reset();

}

});

});

}
);

</script>

<?php

}
/*
|--------------------------------------------------------------------------
| AJAX CREATE EVENT
|--------------------------------------------------------------------------
*/

add_action(
'wp_ajax_create_event',
'create_event_ajax'
);

add_action(
'wp_ajax_nopriv_create_event',
'create_event_ajax'
);


function create_event_ajax()
{

if(
!isset($_POST['nonce'])
||
!wp_verify_nonce(
$_POST['nonce'],
'create_event_action'
)
){
wp_send_json_error('Security check failed');
}

if(
!isset($_POST['event_name'])
||
empty($_POST['event_name'])
){
wp_send_json_error('Event name is required');
}

if(
!isset($_POST['event_date'])
||
empty($_POST['event_date'])
){
wp_send_json_error('Event date is required');
}

$post_id =
wp_insert_post(
[

'post_type'=>'event',

'post_title'=>
sanitize_text_field(
$_POST['event_name']
),

'post_status'=>
'publish'

]
);


if(isset($_POST['event_date'])){
update_post_meta(

$post_id,

'event_date',

sanitize_text_field(
$_POST['event_date']
)

);
}

if(isset($_POST['event_location'])){
update_post_meta(

$post_id,

'event_location',

sanitize_text_field(
$_POST['event_location']
)

);
}

if(isset($_POST['event_venue'])){
update_post_meta(

$post_id,

'event_venue',

sanitize_text_field(
$_POST['event_venue']
)

);
}

if(isset($_POST['tickets'])){
update_post_meta(

$post_id,

'tickets',

intval(
$_POST['tickets']
)

);
}

if(isset($_POST['event_type'])){
wp_set_object_terms(

$post_id,

sanitize_text_field(
$_POST['event_type']
),

'event_type'

);
}

if(is_wp_error($post_id)){
wp_send_json_error('Failed to create event');
}



if(
!empty(
$_FILES['event_image']['name']
)
){

require_once
ABSPATH .
'wp-admin/includes/file.php';

require_once
ABSPATH .
'wp-admin/includes/media.php';

require_once
ABSPATH .
'wp-admin/includes/image.php';


$image =
media_handle_upload(
'event_image',
$post_id
);


if(
!is_wp_error(
$image
)
){

set_post_thumbnail(
$post_id,
$image
);

}

}


wp_send_json_success(

'Event Created Successfully'

);

}
/*
|--------------------------------------------------------------------------
| EVENT GRID SHORTCODE
|--------------------------------------------------------------------------
*/

add_shortcode(
'event_listing',
'event_listing_shortcode'
);


function event_listing_shortcode()
{

$query =
new WP_Query(
[

'post_type'=>'event',

'post_status'=>'publish',

'posts_per_page'=>-1

]
);


ob_start();

?>
<style>

.event-grid{

display:grid;

grid-template-columns:
repeat(
3,
1fr
);

gap:
30px;

}



.event-card{

border:
1px solid #ddd;

padding:
20px;

border-radius:
12px;

text-align:
center;

}



.event-image{

width:
100%;

height:
250px;

object-fit:
cover;

border-radius:
10px;

}



.ticket-btn{

display:
inline-block;

margin-top:
15px;

padding:
12px 24px;

background:
black;

color:
white;

text-decoration:
none;

border-radius:
8px;

}



@media(
max-width:
991px
){

.event-grid{

grid-template-columns:
repeat(
2,
1fr
);

}

}



@media(
max-width:
767px
){

.event-grid{

grid-template-columns:
1fr;

}

}

</style>
<div
class="event-grid"
>

<?php


while(
$query->have_posts()
){

$query->the_post();


$image =
get_the_post_thumbnail_url(
get_the_ID(),
'medium'
)

?: '';

?>


<div
class="event-card"
>


<?php
if(
$image
){
?>

<img

src="<?php
echo esc_url(
$image
);
?>"

class="event-image"

>

<?php
}
?>


<h3>

<?php
the_title();
?>

</h3>


<p>

Date:
<?php

echo
esc_html(

get_post_meta(
get_the_ID(),
'event_date',
true
)

);

?>

</p>



<p>

Location:

<?php

echo
esc_html(

get_post_meta(
get_the_ID(),
'event_location',
true
)

);

?>

</p>



<p>

Tickets:

<?php

echo
esc_html(

get_post_meta(
get_the_ID(),
'tickets',
true
)

);

?>

</p>


<button

type="button"

class="ticket-btn"

data-id="<?php
echo get_the_ID();
?>"

data-tickets="<?php

echo
get_post_meta(
get_the_ID(),
'tickets',
true
);

?>"

>

Get Tickets

</button>

</div>


<?php

}

wp_reset_postdata();

?>

</div>

<?php

return
ob_get_clean();

}
add_action(
'wp_footer',
function(){

?>

<div
id="ticket-popup"
style="display:none"
>

<div
class="popup-box"
>

<h3>
Purchase Tickets
</h3>


<label>

Quantity

</label>


<div>

<button
id="minus"
>

-

</button>


<input

type="number"

id="qty"

value="1"

readonly

>


<button
id="plus"
>

+

</button>

</div>



<div
id="ticket-users"
></div>



<button
id="buy-ticket"
>

Submit

</button>


<button
id="close-popup"
>

Close

</button>

</div>

</div>


<style>

#ticket-popup{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,.5);

display:none;

justify-content:center;
align-items:center;

z-index:99999;
}

#ticket-popup.active{
display:flex;
}


.popup-box{

background:white;

padding:30px;

width:500px;

border-radius:10px;

}


.ticket-name{

width:100%;

margin-top:10px;

padding:10px;

}

.toast{
position:fixed !important;
top:30px !important;
right:30px !important;
background:#28a745 !important;
color:#fff !important;
padding:15px 20px !important;
border-radius:8px !important;
display:none;
z-index:999999999 !important;
box-shadow:0 5px 15px rgba(0,0,0,.2);
}

</style>


<script>

jQuery(function($){

let available = 0;
let selectedEvent = 0;



$('.ticket-btn').on('click',function(){

selectedEvent =
$(this).data('id');

available =
parseInt(
$(this).data('tickets')
);

if(available<=0){

showToast(
'Sold Out'
);

return;

}

$('#qty').val(1);

createFields();

$('#ticket-popup')
.addClass('active')
.css('display','flex')
.hide()
.fadeIn(200);

});




$('#plus').on('click',function(){

let qty =
parseInt(
$('#qty').val()
);

qty++;

if(qty>available){

showToast(
'Only '+available+' tickets are available'
);

return;

}

$('#qty').val(qty);

createFields();

});




$('#minus').on('click',function(){

let qty =
parseInt(
$('#qty').val()
);

if(qty>1){

qty--;

$('#qty').val(qty);

createFields();

}

});





function createFields(){

let qty =
parseInt(
$('#qty').val()
);

let html='';

for(
let i=1;
i<=qty;
i++
){

html+=
'<input type="text" class="ticket-name" placeholder="Person '+i+' Name">';

}

$('#ticket-users')
.html(html);

}





function showToast(msg){

$('#event-toast').remove();

$('body').append(
'<div id="event-toast">'+msg+'</div>'
);

$('#event-toast').css({

position:'fixed',

top:'20px',

right:'20px',

background:'#000',

color:'#fff',

padding:'15px 25px',

borderRadius:'8px',

zIndex:'999999999'

});

setTimeout(function(){

$('#event-toast').fadeOut(300,function(){

$(this).remove();

});

},2500);

}





$('#buy-ticket').on('click',function(){

let qty =
parseInt(
$('#qty').val()
);

let valid=true;



$('.ticket-name').each(function(){

if(
$(this)
.val()
.trim()===''
){

valid=false;

}

});



if(!valid){

showToast(
'Please enter all names'
);

return;

}



$.ajax({

url:'<?php echo admin_url("admin-ajax.php"); ?>',

type:'POST',

data:{

action:'purchase_ticket',

event_id:
selectedEvent,
qty:qty

},

success:function(response){

if(response.success){

showToast(
'Tickets purchased successfully'
);

setTimeout(function(){

location.reload();

},1500);

}else{

showToast(
response.data
);

}

}

});

});



$('#close-popup').on('click',function(){

$('#ticket-popup')
.fadeOut(200,function(){

$(this)
.removeClass('active');

});

});



createFields();

});

</script>

<?php

}
);
add_action(
'wp_ajax_purchase_ticket',
'purchase_ticket'
);

add_action(
'wp_ajax_nopriv_purchase_ticket',
'purchase_ticket'
);

function purchase_ticket(){

$event_id=
intval(
$_POST['event_id']
);

$qty=
intval(
$_POST['qty']
);


$current=
intval(
get_post_meta(
$event_id,
'tickets',
true
)
);


if(
$qty>$current
){

wp_send_json_error(
'Not enough tickets'
);

}


$new=
$current-$qty;


update_post_meta(

$event_id,

'tickets',

$new

);


wp_send_json_success();

}