/**
 * LPA Results Handler
 * Displays core metrics and recommendations without dependencies
 * 
 * @package LeadershipPipelineAssessment
 * @version 1.1.0
 */

class LPA_Minimal_Results {
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        add_shortcode('lpa_results', array($this, 'display_results'));
    }
    
    /**
     * Main shortcode handler
     */
    public function display_results($atts) {
        // Get assessment ID from URL or attribute
        $assessment_id = isset($_GET['assessment']) ? intval($_GET['assessment']) : 0;
        if (!$assessment_id && isset($atts['id'])) {
            $assessment_id = intval($atts['id']);
        }
        
        if (!$assessment_id) {
            return '<p>No assessment specified.</p>';
        }
        
        try {
            // Get core results
            $results = $this->get_core_results($assessment_id);
            if (!$results) {
                return '<p>Assessment results not found.</p>';
            }
            
            // Get recommendations
            $recommendations = $this->get_recommendations($assessment_id);
            
            // Build output HTML with inline styles for reliability
            $output = '<div style="max-width: 800px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
            
            // Add assessment info
            $output .= $this->build_header($results);
            
            // Add core metrics
            $output .= $this->build_metrics_display($results);
            
            // Add recommendations
            if (!empty($recommendations)) {
                $output .= $this->build_recommendations_display($recommendations);
            }
            
            $output .= '</div>';
            
            return $output;
            
        } catch (Exception $e) {
            error_log('LPA Results Error: ' . $e->getMessage());
            return '<p>Error retrieving assessment results.</p>';
        }
    }
    
    /**
     * Get core assessment results
     */
    private function get_core_results($assessment_id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT 
                r.pipeline_health,
                r.decision_index,
                r.risk_level,
                r.growth_capacity,
                r.leadership_density,
                a.respondent_name,
                a.job_title,
                a.company_size,
                a.tech_team_size,
                DATE_FORMAT(r.created_at, '%M %d, %Y') as assessment_date
            FROM {$this->wpdb->prefix}zwFlSeMJu_lpa_results r
            JOIN {$this->wpdb->prefix}zwFlSeMJu_lpa_assessments a 
                ON r.assessment_id = a.assessment_id
            WHERE r.assessment_id = %d",
            $assessment_id
        ));
    }
    
    /**
     * Get recommendations for assessment
     */
    private function get_recommendations($assessment_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT r.recommendation, r.impact_description, r.implementation_steps, r.priority
            FROM {$this->wpdb->prefix}zwFlSeMJu_lpa_recommendations r
            JOIN {$this->wpdb->prefix}zwFlSeMJu_lpa_assessment_recommendations ar 
                ON r.recommendation_id = ar.recommendation_id
            WHERE ar.assessment_id = %d
            ORDER BY FIELD(r.priority, 'critical', 'high', 'medium', 'low')",
            $assessment_id
        ));
    }
    
    /**
     * Build header section
     */
    private function build_header($results) {
        $header = '<div style="margin-bottom: 30px;">';
        $header .= '<h1 style="color: #2c5282; margin-bottom: 10px;">Leadership Pipeline Assessment Results</h1>';
        $header .= sprintf(
            '<p style="color: #4a5568; margin-bottom: 5px;">%s - %s</p>',
            esc_html($results->respondent_name),
            esc_html($results->job_title)
        );
        $header .= sprintf(
            '<p style="color: #718096; font-size: 0.9em;">Assessment Date: %s</p>',
            esc_html($results->assessment_date)
        );
        $header .= '</div>';
        
        return $header;
    }
    
    /**
     * Build metrics display
     */
    private function build_metrics_display($results) {
        $metrics = [
            'Pipeline Health' => ['value' => $results->pipeline_health, 'suffix' => '%'],
            'Decision Index' => ['value' => $results->decision_index, 'suffix' => '%'],
            'Risk Level' => ['value' => number_format($results->risk_level), 'suffix' => ''],
            'Growth Capacity' => ['value' => $results->growth_capacity, 'suffix' => '%'],
            'Leadership Density' => ['value' => $results->leadership_density, 'suffix' => '%']
        ];
        
        $output = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">';
        
        foreach ($metrics as $label => $data) {
            $output .= sprintf(
                '<div style="background: #f7fafc; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="color: #4a5568; margin: 0 0 10px 0; font-size: 1em;">%s</h3>
                    <p style="color: #2d3748; font-size: 1.5em; font-weight: bold; margin: 0;">%s%s</p>
                </div>',
                esc_html($label),
                esc_html($data['value']),
                esc_html($data['suffix'])
            );
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Build recommendations display
     */
    private function build_recommendations_display($recommendations) {
        $output = '<div style="margin-top: 40px;">';
        $output .= '<h2 style="color: #2c5282; margin-bottom: 20px;">Key Recommendations</h2>';
        
        $priority_colors = [
            'critical' => '#feb2b2',
            'high' => '#fbd38d',
            'medium' => '#9ae6b4',
            'low' => '#90cdf4'
        ];
        
        foreach ($recommendations as $rec) {
            $bg_color = $priority_colors[$rec->priority] ?? '#f7fafc';
            
            $output .= sprintf(
                '<div style="background: white; border-left: 4px solid %s; padding: 20px; margin-bottom: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <span style="background: %s; color: #2d3748; padding: 2px 8px; border-radius: 9999px; font-size: 0.8em; text-transform: uppercase;">%s</span>
                    </div>
                    <h3 style="color: #2d3748; margin: 0 0 10px 0;">%s</h3>
                    <p style="color: #4a5568; margin: 0 0 15px 0;">%s</p>
                    <div style="color: #718096; white-space: pre-line;">%s</div>
                </div>',
                $bg_color,
                $bg_color,
                esc_html(ucfirst($rec->priority)),
                esc_html($rec->recommendation),
                esc_html($rec->impact_description),
                esc_html($rec->implementation_steps)
            );
        }
        
        $output .= '</div>';
        return $output;
    }
}

// Initialize handler
add_action('init', function() {
    new LPA_Minimal_Results();
});
