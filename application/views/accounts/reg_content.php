<h2>Register</h2>
<div class="ui-widget-content ui-corner-all">
<?=form_open('accounts/register')?>
<span style="margin: 0 5px 0 5px; float: left">Email</span><?=form_input(array('name' => 'reg_email'))?><br />
<span style="margin: 0 5px 0 5px; float: left">Confirm Email</span><?=form_input(array('name' => 'confirm_email'))?><br />
<span style="margin: 0 5px 0 5px; float: left">Password</span><?=form_password(array('name' => 'reg_password'))?><br />
<span style="margin: 0 5px 0 5px; float: left">Confirm Password</span><?=form_password(array('name' => 'confirm_password'))?><br />
<span style="margin: 0 5px 0 5px; float: left">First Name</span><?=form_input(array('name' => 'first_name'))?><br />
<span style="margin: 0 5px 0 5px; float: left">Last Name</span><?=form_input(array('name' => 'last_name'))?><br />
<?=form_submit(array('id' => 'register', 'name' => 'register'), 'Register')?>
<?=form_close()?>
</div>