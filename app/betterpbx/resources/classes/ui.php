<?php
/*
	BetterPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is BetterPBX

	The Initial Developer of the Original Code is
	Mitchell R <github.com/mrinc>
	Portions created by the Initial Developer are Copyright (C) 2024-2025
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mitchell R <github.com/mrinc>
*/

require_once dirname(__DIR__, 4) . "/resources/require.php";

if (!class_exists('BPPBX_UI')) {
  class BPPBX_UI {
    public static function actionBar($titleKey, $actions = []) {
      if (!isset($language)) {
        $language = new text;
      }
      $text = $language->get();
      echo "<div class='action_bar' id='action_bar'>";
      echo "	<div class='heading'><b>".$text[$titleKey]."</b></div>";
      echo "	<div class='actions'>";
      foreach ($actions as $action) {
        echo $action;
      }
      echo "	</div>";
      echo "	<div style='clear: both;'></div>";
      echo "</div>";
    }

    public static function form($id = "myform", $path = "myform.php", $fields = []) {
      echo "<form id='".$id."' method='post' action='".$path."'>";
      echo "<input type='hidden' name='action' value='save'>";
      foreach ($fields as $field) {
        echo $field;
      }
      echo "</form>";
    }

    public static function card($header = [], $content = [], $footer = []) {
      echo "<div class='card'>";
      if ($header && is_array($header) && count($header) > 0) {
        echo "<div class='card-header'>";
        foreach ($header as $h) {
          echo $h;
        }
        echo "</div>";
      }
      if ($content && is_array($content) && count($content) > 0) {
        echo "<div class='card-content'>";
        foreach ($content as $c) {
          echo $c;
        }
        echo "</div>";
      }
      if ($footer && is_array($footer) && count($footer) > 0) {
        echo "<div class='card-footer'>";
        foreach ($footer as $f) {
          echo $f;
        }
        echo "</div>";
      }
      echo "</div>";
    }
    public static function field($type, $name, $label, $value, $description, $opts = []) {
      echo "<div class='form-group'>";
      echo "	<label for='".$name."' class='form-label'>".escape($label)."</label>";
      if ($type == 'select') {
        echo "	<select class='form-control' id='".$name."' name='".$name."'>";
        foreach ($opts as $opt) {
          echo "	<option value='".$opt['value']."'";
          if ($value == $opt['value']) {
            echo " selected='selected'";
          }
          echo ">".escape($opt['label'])."</option>";
        }
        echo "</select>";
      } else {
        echo "	<input type='".$type."' class='form-control' id='".$name."' name='".$name."' value='".escape($value)."' aria-describedby='".$name."-help'>";
      }
      if (isset($description) && $description != "") {
        echo "	<div id='".$name."-help' class='form-text'>".escape($description)."</div>";
      }
      echo "</div>";
    }
    public static function button($type, $label, $icon = '', $id = '', $link = '', $onclick = '', $collapse = 'never') {
      echo "<button type='".$type."' class='btn btn-primary' id='".$id."' onclick='".$onclick."' data-bs-toggle='collapse' data-bs-target='#".$collapse."'>".$icon." ".$label."</button>";
    }
  }
}