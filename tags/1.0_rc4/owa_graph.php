<?php

//
// Open Web Analytics - An Open Source Web Analytics Framework
//
// Copyright 2006 Peter Adams. All rights reserved.
//
// Licensed under GPL v2.0 http://www.gnu.org/copyleft/gpl.html
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//
// $Id$
//

require_once (OWA_BASE_DIR . '/owa_api.php');
require_once (OWA_BASE_DIR . '/owa_error.php');
require_once (OWA_BASE_DIR . '/owa_lib.php');
require_once (OWA_JPGRAPH_DIR.'/jpgraph.php');

/**
 * Graph Generator
 * 
 * @author      Peter Adams <peter@openwebanalytics.com>
 * @copyright   Copyright &copy; 2006 Peter Adams <peter@openwebanalytics.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GPL v2.0
 * @category    owa
 * @package     owa
 * @version		$Revision$	      
 * @since		owa 1.0.0
 */
class owa_graph {

	/**
	 * Current Time
	 *
	 * @var array
	 */
	var $time_now;
	
	/**
	 * Graph Data
	 *
	 * @var array
	 */
	var $data = array();
	
	/**
	 * Graph Parameters
	 *
	 * @var array
	 */
	var $params = array();
	
	/**
	 * Graph Height
	 *
	 * @var integer
	 */
	var $height = 200;
	
	/**
	 * Graph Width
	 *
	 * @var integer
	 */
	var $width = 400;
	
	/**
	 * Image Format
	 *
	 * @var string
	 */
	var $image_format = "png";
	
	/**
	 * Metrics
	 *
	 * @var unknown_type
	 */
	var $metrics;
	
	/**
	 * API type
	 *
	 * @var string
	 */
	var $api_type = 'graph';
	
	/**
	 * API Calls
	 *
	 * @var array
	 */
	var $api_calls = array();
	
	/**
	 * Configuration
	 *
	 * @var array
	 */
	var $config;
	
	/**
	 * Error handler
	 *
	 * @var object
	 */
	var $e;

	/**
	 * Constructor
	 *
	 * @return owa_graph
	 * @access public
	 */
	function owa_graph() {
		
		$this->config = &owa_settings::get_settings();
		$this->e = &owa_error::get_instance();
		
		// Set current time
		$this->time_now = owa_lib::time_now();
		
		// Fetch all  metrics objects through the api
		$this->metrics = owa_api::get_instance('metric');
		
		return;
	}
	
	function graph($type) {
		
		switch ($type) {
			
			case "bar":
				$this->bar_graph();
				break;
			case "line":
				$this->line_graph();
				break;
			case "pie":
				$this->pie_graph();
				break;
			case "bar_line":
				$this->bar_line_graph();
				break;
			case "stacked_area":
				$this->stacked_area_graph();
				break;
			default:
				$this->bar_graph();
			
		}
		
		return;
		
	}

	/**
	 * Line Graph Wrapper
	 * 
	 */
	function line_graph() {
	
		require_once (OWA_JPGRAPH_DIR.'/jpgraph_line.php');
		
		$datay = $this->data['datay'];
		$graph = new Graph($this->width,$this->height,"auto");
		$graph->SetScale("textlin");
		$graph->img->SetImgFormat($this->image_format);
		$graph->img->SetMargin(40,40,40,40);    
		$graph->SetShadow();
		
		$graph->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->xaxis->SetTickLabels($this->data['datax']);
		
		$graph->title->Set($this->params['graph_title']);
		$graph->xaxis->title->Set($this->params['xaxis_title']);
		$graph->yaxis->title->Set($this->params['yaxis_title']);
		
		$p1 = new LinePlot($datay);
		$p1->SetFillColor("orange");
		$p1->mark->SetType(MARK_FILLEDCIRCLE);
		$p1->mark->SetFillColor("red");
		$p1->mark->SetWidth(2);
		$graph->Add($p1);
		
		$graph->Stroke();
		
		return;
	}
	
	/**
	 * Vertical Bar Graph
	 *
	 */
	function bar_graph() {
	
		require_once (OWA_JPGRAPH_DIR .'/jpgraph_bar.php');
	
		$datay = $this->data['datay'];
	
		// Create the graph. These two calls are always required
		$graph = new Graph($this->params['width'],$this->params['height'],"auto"); 
		$graph->SetScale("textlin");
		$graph->img->SetImgFormat($this->image_format);
		$graph->SetBackgroundGradient('white','white'); 
	
		// Add a drop shadow
		//$graph->SetShadow();
		
		// Adjust the margin a bit to make more room for titles
		$graph->img->SetMargin(40,30,20,40);
		
		// Create a bar pot
		$bplot = new BarPlot($datay);
		
		$bplot->SetFillColor('orange');
		$bplot->SetWidth(1.0);
		//$bplot->SetValuePos('top'); 
		$graph->Add($bplot);
		
		// Setup the titles
		$graph->title->Set($this->params['graph_title']);
		$graph->xaxis->SetTickLabels($this->data['datax']);
		$graph->xaxis->SetLabelAngle(90); 
		$graph->xaxis->title->Set($this->params['xaxis_title']);
		$graph->yaxis->title->Set($this->params['yaxis_title']);
		
		$graph->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
		
		// Display the graph
		$graph->Stroke();
		
		return;
	}
	
	/**
	 * Pie Graph
	 *
	 */
	function pie_graph() {
	
		require_once (OWA_JPGRAPH_DIR .'/jpgraph_pie.php');

		$data = $this->data['data_pie'];
		
		// Create the Pie Graph.
		$graph = new PieGraph($this->params['width'],$this->params['height']);
		
		// Set A title for the plot
		//$graph->title->Set($this->params['graph_title']);
		$graph->title->SetFont(FF_FONT1,FS_BOLD); 
		$graph->title->SetColor("black");
		$graph->legend->SetAbsPos(10,10, 'right', 'top');
		$graph->legend->SetColumns(3); 		
		
		// Create pie plot
		$p1 = new PiePlot($data);
		$p1->SetCenter(0.5,0.55);
		$p1->SetSize(0.3);
		$p1->value->HideZero();
		
		// Enable and set policy for guide-lines
		$p1->SetGuideLines();
		$p1->SetGuideLinesAdjust(1.4);
		
		// Setup the labels
		$p1->SetLabelType(PIE_VALUE_ABS);    
		$p1->value->Show();            
		$p1->value->SetFont(FF_FONT1,FS_BOLD);    
		$p1->value->SetFormat('%d '.$this->params['slice_label']);        
		
		$p1->SetLegends($this->params['legends']);
		
		// Add and stroke
		$graph->Add($p1);
		
		$graph->Stroke();
		
		return;
	}
	
	function bar_line_graph() {
	
		include_once (OWA_JPGRAPH_DIR .'/jpgraph_line.php');
		include_once (OWA_JPGRAPH_DIR .'/jpgraph_bar.php');
		
		$data_y1 = $this->data['y1'];
		$data_y2 = $this->data['y2'];
		
		$datax = $this->data['x'];
		
		// Create the graph. 
		$graph = new Graph($this->params['width'],$this->params['height']);    
		//$graph->img->SetAntiAliasing();
		$graph->SetColor('white'); 
		$graph->SetMarginColor('white'); 
		$graph->SetFrame(true,'silver',1); 
		$graph->SetScale("textlin");
		$graph->SetMargin(40,40,20,40);
		//$graph->SetShadow();
		$graph->xaxis->SetTickLabels($datax);
		$graph->xaxis->SetLabelAngle(90); 
		
		// Create the linear error plot
		$l1plot = new LinePlot($data_y1);
		$l1plot->SetColor("lightblue");
		$l1plot->SetWeight(1);
		$l1plot->SetFillColor("lightblue@0.2");
		$l1plot->SetLegend($this->params['y1_title']);	
		//Center the line plot in the center of the bars
		$l1plot->SetBarCenter();
	
		// Create the bar plot
		$bplot = new BarPlot($data_y2);
		$bplot->SetFillColor("orange");
		$bplot->SetWidth(1.0);
		$bplot->SetLegend($this->params['y2_title']);
		
		// Add the plots to the graph
		$graph->Add($bplot);
		$graph->Add($l1plot);
		
		
		$graph->title->Set($this->params['graph_title']);
		$graph->xaxis->title->Set($this->params['xaxis_title']);
		$graph->yaxis->title->Set($this->params['yaxis_title']);
		
		$graph->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
		
		
		// Display the graph
		$graph->Stroke();
		
		return;
	}
	
	function stacked_area_graph() {
			
		include_once (OWA_JPGRAPH_DIR .'/jpgraph_line.php');
		
		$data_y1 = $this->data['y1'];
		$data_y2 = $this->data['y2'];
		$datax = $this->data['x'];
		
		// Create the graph. 
		$graph = new Graph($this->params['width'],$this->params['height']);    
		//$graph->img->SetAntiAliasing();
		$graph->SetColor('white'); 
		$graph->SetMarginColor('white'); 
		$graph->SetFrame(true,'silver',1); 
		$graph->SetScale("textlin");
		$graph->SetMargin(40,40,20,40);
		$graph->xaxis->SetTickLabels($datax);
		$graph->xaxis->SetLabelAngle(90); 
		
		// Create the linear plots for each category
		$dplot[] = new LinePLot($datay1);
		$dplot[] = new LinePLot($datay2);
		$dplot[] = new LinePLot($datay3);
		
		$dplot[0]->SetFillColor("red");
		$dplot[1]->SetFillColor("blue");
		$dplot[2]->SetFillColor("green");
		
		// Create the accumulated graph
		$accplot = new AccLinePlot($dplot);
		
		// Add the plot to the graph
		$graph->Add($accplot);
		
		$dplot->SetLegend($this->params['y1_title']);	
		
		$graph->title->Set($this->params['graph_title']);
		$graph->xaxis->title->Set($this->params['xaxis_title']);
		$graph->yaxis->title->Set($this->params['yaxis_title']);
		
		$graph->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
		$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
		
		// Display the graph
		$graph->Stroke();
		
		return;
	}
	
	function error_graph($msg = 'There is no Data to Graph') {
		
		include_once (OWA_JPGRAPH_DIR .'/jpgraph_canvas.php');
		
		$graph = new CanvasGraph($this->params['width'], $this->params['height']);    
		
		$t1 = new Text($msg); 
		$t1->Pos(0.05, 0.1); 
		$t1->SetOrientation('h'); 
		$t1->SetFont(FF_FONT1, FS_BOLD); 
		$t1->SetColor('orange'); 
		$graph->AddText($t1); 
		$graph->Stroke(); 
		return; 
	}
			
	
	/**
	 * Get Display Label for Reporting Period
	 *
	 * @param string $period
	 * @return string $label
	 * @access public
	 */
	function get_period_label($period) {
		
		return owa_lib::get_period_label($period);
	}
	
	/**
	 * makes linear date scale for x axis
	 *
	 * @param array $variable
	 * @param string $label
	 * @param string $delim
	 * @return array
	 */
	function make_date_label($variable, $label, $delim = '/') {
	
		$date = array();
		foreach ($variable as $key => $value) {
					
					$date[$key] = $label[$key].$delim.$value;
					
				}
		
		return $date;
	}
	
	function get_month_label($month) {
		
		return owa_lib::get_month_label($month);
	}

}

?>
