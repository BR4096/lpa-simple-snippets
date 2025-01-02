/**
 * Fixed Form Handler for LPA with Correct Table Names
 * @version 1.0.6
 */

// Define table names
define('LPA_TABLE_PREFIX', 'zwFISeJMu_');
define('LPA_TABLE_ASSESSMENTS', LPA_TABLE_PREFIX . 'lpa_assessments');
define('LPA_TABLE_METRICS', LPA_TABLE_PREFIX . 'lpa_metrics');
define('LPA_TABLE_RESULTS', LPA_TABLE_PREFIX . 'lpa_results');
define('LPA_TABLE_RECOMMENDATIONS', LPA_TABLE_PREFIX . 'lpa_recommendations');
define('LPA_TABLE_ASSESSMENT_RECOMMENDATIONS', LPA_TABLE_PREFIX . 'lpa_assessment_recommendations');

global $lpa_current_assessment_id;

// Form submission handler
add_action('gform_after_submission_2', function($entry, $form) {
    global $wpdb, $lpa_current_assessment_id;
    
    error_log('LPA Debug: Form submission received for entry ' . $entry['id']);
    error_log('LPA Debug: Full entry data: ' . print_r($entry, true));
    
    try {
        // Handle complex name field
        $first_name = rgar($entry, '24.3');
        $last_name = rgar($entry, '24.6');
        $full_name = trim($first_name . ' ' . $last_name);
        
        // Get workforce deployment value
        $workforce_deployment = rgar($entry, '38');
        error_log('LPA Debug: Workforce Deployment value: ' . $workforce_deployment);
        
        // Prepare assessment data
        $assessment_data = array(
            'entry_id' => $entry['id'],
            'respondent_name' => $full_name,
            'respondent_email' => rgar($entry, '3'),
            'job_title' => rgar($entry, '4'),
            'company_size' => rgar($entry, '5'),
            'tech_team_size' => intval(rgar($entry, '6')),
            'business_model' => rgar($entry, '7'),
            'tech_complexity' => rgar($entry, '8'),
            'workforce_deployment' => rgar($entry, '9')
        );
        
        error_log('LPA Debug: Assessment data to insert: ' . print_r($assessment_data, true));
        
        // Insert assessment with explicit format specifiers
        $result = $wpdb->insert(
            LPA_TABLE_ASSESSMENTS,
            $assessment_data,
            array(
                '%d',  // entry_id
                '%s',  // respondent_name
                '%s',  // respondent_email
                '%s',  // job_title
                '%s',  // company_size
                '%d',  // tech_team_size
                '%s',  // business_model
                '%s',  // tech_complexity
                '%s'   // workforce_deployment
            )
        );
        
        if ($result === false) {
            throw new Exception('Failed to insert assessment data: ' . $wpdb->last_error);
        }
        
        $assessment_id = $wpdb->insert_id;
        error_log('LPA Debug: Assessment ID Created: ' . $assessment_id);
        
        // Store metrics
        $metrics_data = array(
            'assessment_id' => $assessment_id,
            'decision_effectiveness' => intval(rgar($entry, '10')),
            'team_autonomy' => intval(rgar($entry, '25')),
            'leadership_success' => intval(rgar($entry, '36')),
            'ready_leaders' => intval(rgar($entry, '27')),
            'dependencies' => intval(rgar($entry, '28'))
        );
        
        $result = $wpdb->insert(
            LPA_TABLE_METRICS,
            $metrics_data,
            array('%d', '%d', '%d', '%d', '%d', '%d')
        );
        
        if ($result === false) {
            throw new Exception('Failed to insert metrics data: ' . $wpdb->last_error);
        }
        
        // Store results
        $results_data = array(
            'assessment_id' => $assessment_id,
            'pipeline_health' => floatval(rgar($entry, '40')),
            'decision_index' => floatval(rgar($entry, '41')),
            'risk_level' => floatval(rgar($entry, '42')),
            'growth_capacity' => floatval(rgar($entry, '43')),
            'leadership_density' => floatval(rgar($entry, '44'))
        );
        
        $result = $wpdb->insert(
            LPA_TABLE_RESULTS,
            $results_data,
            array('%d', '%f', '%f', '%f', '%f', '%f')
        );
        
        if ($result === false) {
            throw new Exception('Failed to insert results data: ' . $wpdb->last_error);
        }
        
        // Store assessment ID for redirect
        $lpa_current_assessment_id = $assessment_id;
        
        // Store in session as backup
        if (!session_id()) {
            session_start();
        }
        $_SESSION['lpa_assessment_id'] = $assessment_id;
        
        // Create results URL
        $results_url = home_url('/lpa-results/?assessment=' . $assessment_id);
        error_log('LPA Debug: Results URL created: ' . $results_url);
        
    } catch (Exception $e) {
        error_log('LPA Error: ' . $e->getMessage());
        wp_die('An error occurred processing your submission. Please contact support.');
    }
}, 5, 2);

// Results shortcode with correct table names
add_shortcode('lpa_results', function($atts) {
    global $wpdb;
    
    error_log('LPA Debug: Shortcode called with REQUEST: ' . print_r($_REQUEST, true));
    
    // Get assessment ID from URL
    $assessment_id = filter_input(INPUT_GET, 'assessment', FILTER_VALIDATE_INT);
    error_log('LPA Debug: Parsed Assessment ID: ' . var_export($assessment_id, true));
    
    if (!$assessment_id) {
        return '<p>No assessment specified. Please submit an assessment first.</p>';
    }
    
    try {
        // Query for results
        $query = $wpdb->prepare(
            "SELECT r.*, a.respondent_name, a.job_title, a.company_size, a.workforce_deployment
             FROM " . LPA_TABLE_RESULTS . " r
             JOIN " . LPA_TABLE_ASSESSMENTS . " a ON r.assessment_id = a.assessment_id
             WHERE r.assessment_id = %d",
            $assessment_id
        );
        
        error_log('LPA Debug: Executing query: ' . $query);
        
        $results = $wpdb->get_row($query);
        
        if (!$results) {
            error_log('LPA Debug: No results found for assessment ID: ' . $assessment_id);
            return sprintf(
                '<p>Results not found for assessment ID %d. Please try submitting the assessment again.</p>',
                $assessment_id
            );
        }
        
        // [Rest of the display code remains the same]
        
    } catch (Exception $e) {
        error_log('LPA Error in shortcode: ' . $e->getMessage());
        return '<p>Error retrieving results. Please contact support.</p>';
    }
});