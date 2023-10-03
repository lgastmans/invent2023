<?php
  require_once("../include/const.inc.php");
  require_once("session.inc.php");
  require_once("db_mysqli.php");



  /*
    get tax types
  */
  $arr_types = getTaxTypeList();



  /*
    retrieve details
  */
  $id = false;
  if (IsSet($_GET['id'])) // this is the definition_id
    $id = $_GET['id']; 

  $tax_id = false;


  $definition_description = '';
  $definition_percent = 0;
  $definition_type = 0;
  $is_active = 'Y';

  if ($id) {

    $sql = "
      SELECT *
      FROM ".Monthalize('stock_tax_definition')."
      WHERE definition_id = $id";

    $qry = new Query($sql);

    $definition_description = $qry->FieldByName('definition_description');
    $definition_percent = $qry->FieldByName('definition_percent');
    $definition_type = $qry->FieldByName('definition_type');
    $definition_explanation = $qry->FieldByName('definition_explanation');

    $sql = "
      SELECT st.* 
      FROM ".Monthalize('stock_tax_links')." stl
      INNER JOIN ".Monthalize('stock_tax')." st ON (st.tax_id = stl.tax_id)
      WHERE stl.tax_definition_id = $id
    ";

    $qry = new Query($sql);

    $tax_id = $qry->FieldByName('tax_id');
    $is_active = $qry->FieldByName('is_active');

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

      $is_active = 'N';
      if (isset($_POST['is_active']))
        $is_active = 'Y';


      if ($_POST['id']) {


        $sql = "
          UPDATE ".Monthalize('stock_tax_definition')."
          SET 
            definition_description  = '".$_POST['definition_description']."',
            definition_percent      = '".$_POST['definition_percent']."',
            definition_type         = '".$_POST['definition_type']."',
            definition_explanation  = '".$_POST['definition_explanation']."'
          WHERE definition_id       = ".$_POST['id'];

        $qry = new Query($sql);

        $sql2 = "
          UPDATE ".Monthalize('stock_tax')."
          SET
            tax_description = '".$_POST['definition_description']."',
            is_active       = '".$is_active."'
          WHERE tax_id      = ".$_POST['tax_id']."
        ";

        $qry = new Query($sql2);


      }
      else {


        $sql = "
          INSERT INTO ".Monthalize('stock_tax_definition')."
            (definition_description,
            definition_percent,
            definition_type,
            definition_explanation
            ) 
          VALUES (
            '".$_POST['definition_description']."',
            '".$_POST['definition_percent']."',
            '".$_POST['definition_type']."',
            '".$_POST['definition_explanation']."'
          )";

        $qry = new Query($sql);

        $id = $qry->getInsertedID();


        $sql = "
          INSERT INTO ".Monthalize('stock_tax')."
            (tax_description,
            tax_type,
            is_active
            )
          VALUES (
            '".$_POST['definition_description']."',
            '0',
            '".$is_active."'
          )";

        $qry = new Query($sql);

        $tax_id = $qry->getInsertedID();


        $sql = "
          INSERT INTO ".Monthalize('stock_tax_links')."
            (tax_definition_id,
            tax_id
            )
          VALUES(
            '$id',
            '$tax_id'
          )";

          $qry = new Query($sql);


      }


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
        <label for="definition_description">Description</label>
        <input type="text" class="form-control" name="definition_description" id="definition_description" placeholder="Definition" value="<?php echo $definition_description; ?>">
      </div>


      <div class="form-group">
        <label for="definition_percent">Percent</label>
        <input type="text" class="form-control" name="definition_percent" id="definition_percent" placeholder="Percent" value="<?php echo $definition_percent; ?>">
      </div>

      
      <div class="form-group">
        <label for="definition_type">Type</label>
        <select class="form-control" name="definition_type" id="definition_type">
          <?php 
            foreach ($arr_types as $key=>$val) {
              if ($key == $definition_type)
                echo "<option value='".$key."' selected>".$val."</option>";
              else
                echo "<option value='".$key."'>".$val."</option>";
            }
          ?>
        </select>
      </div> 


      <div class="form-group">
        <label for="definition_explanation">Explanation</label>
        <input type="text" class="form-control" name="definition_explanation" id="definition_explanation" placeholder="Explanation" value="<?php echo $definition_explanation; ?>">
      </div>


      <div class="checkbox">
        <label>
          <input type="checkbox" name="is_active" id="is_active" value="" <?php echo ($is_active=='Y' ? "checked" : "");?> >
            Active
        </label>
      </div>

      
      <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" > 
      <input type="hidden" name="tax_id" id="tax_id" value="<?php echo $tax_id; ?>" > 


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