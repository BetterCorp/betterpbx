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
      $output = "<div class='action_bar' id='action_bar'>";
      $output .= "	<div class='heading'><b>".$text[$titleKey]."</b></div>";
      $output .= "	<div class='actions'>";
      foreach ($actions as $action) {
        $output .= $action;
      }
      $output .= "	</div>";
      $output .= "	<div style='clear: both;'></div>";
      $output .= "</div>";
      return $output;
    }

    public static function form($id = "myform", $path = "myform.php", $fields = []) {
      $output = "<form id='".$id."' method='post' action='".$path."'>";
      $output .= "<input type='hidden' name='action' value='save'>";
      foreach ($fields as $field) {
        $output .= $field;
      }
      $output .= "</form>";
      return $output;
    }

    public static function card($header = [], $content = [], $footer = []) {
      $output = "<div class='card'>";
      if ($header && is_array($header) && count($header) > 0) {
        $output .= "<div class='card-header'>";
        foreach ($header as $h) {
          $output .= $h;
        }
        $output .= "</div>";
      }
      if ($content && is_array($content) && count($content) > 0) {
        $output .= "<div class='card-content'>";
        foreach ($content as $c) {
          $output .= $c;
        }
        $output .= "</div>";
      }
      if ($footer && is_array($footer) && count($footer) > 0) {
        $output .= "<div class='card-footer'>";
        foreach ($footer as $f) {
          $output .= $f;
        }
        $output .= "</div>";
      }
      $output .= "</div>";
      return $output;
    }
    public static function field($type, $name, $label, $value, $description, $opts = []) {
      $output = "<div class='form-group'>";
      $output .= "	<label for='".$name."' class='form-label'>".escape($label)."</label>";
      if ($type == 'select') {
        $output .= "	<select class='form-control' id='".$name."' name='".$name."'>";
        foreach ($opts as $opt) {
          $output .= "	<option value='".$opt['value']."'";
          if ($value == $opt['value']) {
            $output .= " selected='selected'";
          }
          $output .= ">".escape($opt['label'])."</option>";
        }
        $output .= "</select>";
      } else {
        $output .= "	<input type='".$type."' class='form-control' id='".$name."' name='".$name."' value='".escape($value)."' aria-describedby='".$name."-help'>";
      }
      if (isset($description) && $description != "") {
        $output .= "	<div id='".$name."-help' class='form-text'>".escape($description)."</div>";
      }
      $output .= "</div>";
      return $output;
    }
    public static function button($type, $label, $icon = '', $id = '', $link = '', $onclick = '', $collapse = 'never') {
      $output = "<button type='".$type."' class='btn btn-primary' id='".$id."'";
      if (isset($onclick) && $onclick != '') {
        $output .= " onclick='".$onclick."'";
      }
      if (isset($link) && $link != '') {
        $output .= " href='".$link."'";
      }
      if (isset($collapse) && $collapse != '') {
        $output .= " data-bs-toggle='collapse' data-bs-target='#".$collapse."'";
      }
      $output .= ">";
      if (isset($icon) && $icon != '') {
        $output .= "<i class='fa ".$icon."'></i>";
      }
      $output .= $label."</button>";
      return $output;
    }
  }
}