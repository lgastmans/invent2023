<?php
  require_once("../include/const.inc.php");
  require_once("session.inc.php");
  require_once("db_mysqli.php");


  /*
    list of categories
  */
  $qry = $conn->query("
    SELECT category_id, category_description
    FROM stock_category
    ORDER BY category_description"
  );

  $arr_cat = array('0'=>'None');
  while ($obj = $qry->fetch_object()) {
    $arr_cat[$obj->category_id] = $obj->category_description;
  }



  /*
    retrieve details
  */
  $id = false;
  if (IsSet($_GET['id'])) 
    $id = $_GET['id']; 


  $category_code = '';
  $category_description = '';
  $parent_category_id = 0;
  $is_perishable = 'N';
  $hsn = '';
  $apply_tax_rule = false;

  if ($id) {

    $sql = "
      SELECT *
      FROM stock_category
      WHERE category_id = $id";

    $qry = new Query($sql);

    $category_code = $qry->FieldByName('category_code');
    $category_description = $qry->FieldByName('category_description');
    $parent_category_id = $qry->FieldByName('parent_category_id');
    $is_perishable = $qry->FieldByName('is_perishable');
    $hsn = $qry->FieldByName('hsn');
    $apply_tax_rule = $qry->FieldByName('apply_tax_rule');

  }



  /*
    save form
  */

  $error = false;

  if (isset($_POST['action'])) {

    $can_save = true;

    if (($_SESSION["int_month_loaded"] == date('n')) && ($_SESSION["int_year_loaded"] == date('Y'))) {
          $msg = 'Updated successfully';
    }
    else {
          $msg = 'Cannot update in previous months. <br> Select the current month/year and continue.';
          $can_save = false;
    }

    if ($can_save) {


      //print_r($_POST);
      //$error=true;

      $is_perishable = 'N';
      if (isset($_POST['is_perishable']))
        $is_perishable = 'Y';

      $apply_tax_rule = false;
      if (isset($_POST['apply_tax_rule']))
        $apply_tax_rule = true;


      if ($_POST['id']) {

        $sql = "
          UPDATE stock_category
          SET 
            category_code           = '".$_POST['category_code']."',
            category_description    = '".$_POST['category_description']."',
            parent_category_id      = '".$_POST['parent_category_id']."',
            hsn                     = '".$_POST['hsn']."',
            is_perishable           = '".$is_perishable."',
            is_modified             = 'Y',
            apply_tax_rule          = '".$apply_tax_rule."'
          WHERE category_id         = ".$_POST['id'];

      }
      else {
        $sql = "
          INSERT INTO stock_category
            (category_code,
            category_description,
            parent_category_id,
            hsn,
            is_perishable,
            is_modified,
            apply_tax_rule
            ) 
          VALUES (
            '".$_POST['category_code']."',
            '".$_POST['category_description']."',
            '".$_POST['parent_category_id']."',
            '".$_POST['hsn']."',
            '".$is_perishable."',
            'Y',
            '".$apply_tax_rule."'
          )";

      }

      $qry = new Query($sql);

      if ($qry->b_error == true) {

        $error = true;
        $msg = "There was an error trying to save the information! ".$qry->err;

      }     
    }
    else {
      $error = true;
    }
  }

?>

<html>

<head>
  <link href="../include/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin:25px;
    }
  </style>
</head>

<body>

  <div class="container">

    <form id="target" method="post" >


      <div id="settings_alert" class="alert alert-danger alert-dismissible" role="alert" style="display:<?php echo (!$error) ? 'none' : 'visible'; ?>;">
        <?php echo $msg;?>
      </div>
      <br><br>


      
      <div class="form-group">
        <label for="category_code">Category Code</label>
        <input type="text" class="form-control" name="category_code" id="category_code" placeholder="Code" value="<?php echo $category_code; ?>">
      </div>


      <div class="form-group">
        <label for="category_description">Category Description</label>
        <input type="text" class="form-control" name="category_description" id="category_description" placeholder="Description" value="<?php echo $category_description; ?>">
      </div>

      
      <div class="form-group">
        <label for="hsn">HSN</label>
        <input type="text" class="form-control" name="hsn" id="hsn" placeholder="HSN" value="<?php echo $hsn; ?>">
      </div>


<!--       <div class="form-group">
        <label for="parent_category_id">Parent Category:</label>
        <select class="form-control" name="parent_category_id" id="parent_category_id">
          <?php 
          /*
            foreach ($arr_cat as $key=>$val) {
              if ($key == $parent_category_id)
                echo "<option value='".$key."' selected>".$val."</option>";
              else
                echo "<option value='".$key."'>".$val."</option>";
            }
          */
          ?>
        </select>
      </div> 
 -->
      
      <div class="checkbox">
        <label>
          <input type="checkbox" name="is_perishable" id="is_perishable" value="" <?php echo ($is_perishable=='Y' ? "checked" : "");?> >
          Perishable
        </label>
      </div>


      <div class="checkbox">
        <label>
          <input type="checkbox" name="apply_tax_rule" id="apply_tax_rule" value="" <?php echo ($apply_tax_rule==true ? "checked" : "");?> >
          Apply garments tax rule <b><i>5% < Rs 1,000.- and 12% > Rs 1,000.-</i></b>
        </label>
      </div>


      <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" > 


      <button type="submit" value="general" name="action" id="btn-general" class="btn btn-primary">Save</button>


    </form>

  </div>

<script src="../include/js/jquery-3.2.1.min.js"></script>
<script src="../include/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
<script src="../include/js/bootbox.min.js"></script>

<script>

    $( document ).ready(function() {

      <?php if ((!$error) && ($can_save)) { ?>

        bootbox.alert("Saved successfully", function() { 

          window.close();

        });

      <?php } ?>

    });

</script>


</body>
</html>     