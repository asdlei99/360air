<h2>Login</h2>
<div class="ui-widget-content ui-corner-all">
<?=form_open('accounts/login')?>
<span style="margin: 0 5px 0 5px; float: left">Email</span><?=form_input(array('name' => 'email'))?><br />
<span style="margin: 0 5px 0 5px; float: left">Password</span><?=form_password(array('name' => 'password'))?><br />
<?=form_submit(array('id' => 'login', 'name' => 'login'), 'Login')?>
<?=form_close()?>
<?=anchor('accounts/resetpw', 'Forgot Password')?><br />
<?=anchor('accounts/showLogin', 'Register Here!')?>
<script>
$(function() {
$( "#login" ).button();
});
</script>
</div>
