<html>
<head><TITLE></TITLE><body>
<form method='post'>
<textarea name='base64text' cols=60 rows=20><? echo htmlentities($_POST['base64text']); ?></textarea><br>
<textarea name='base64' cols=60 rows=20><? echo base64_encode($_POST['base64text']); ?></textarea><br>
<input type='submit' value='Encode'>
</form>
</body></head></html>