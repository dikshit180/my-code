<form id="pva_form" enctype="multipart/form-data">
    <input id="post_title" name="post_title" type="text" placeholder="Post Title" required />
    <input id="post_featured_image" name="post_featured_image" type="file" accept="image/*" />
    <button id="create_post" type="button">Create Post</button>
	 <div id="loading" style="display: none;">Loading...</div>
	 <div id="success" style="display: none; color: green;">success...</div>
	 <div id="message" style="margin-top: 10px; color: red;"></div> 
</form>
