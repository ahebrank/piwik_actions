<?php

namespace Drupal\piwik_actions;

/**
 *
 */
class Visits {

  /**
   *
   * @param [string] $endpoint_base
   */
  public function __construct($endpoint_base) {
    $this->endpoint_base = $endpoint_base;
  }

  /**
   * Return a bunch of action rows, with custom variables.
   *
   * @param [type] $filters
   *
   * @return array
   */
  public function getActions($filters) {
    $visits = $this->getLastVisitsDetails($filters['start_date'], $filters['end_date']);
    $action_type = isset($filters['action']) ? $filters['action'] : NULL;
    $rows = [];
    foreach ($visits as $visit) {
      $visit_details = [];
      foreach ($visit['customVariables'] as $i => $var) {
        $key = 'customVariableName' . $i;
        $val = 'customVariableValue' . $i;
        $visit_details[$var[$key]] = $var[$val];
      }
      foreach ($visit['actionDetails'] as $action) {
        // Filter action.
        if ($action_type && $action['type'] != $action_type) {
          continue;
        }
        $row_data = [
          'url' => $action['url'],
          'time' => date('Y-m-d g:ia', $action['timestamp']),
        ] + $visit_details;
        $rows[] = $row_data;
      }
    }

    \Drupal::service('module_handler')->alter('piwik_actions_data', $rows);

    return $rows;
  }

  /**
   *
   */
  private function getEndpoint($method, $start_date, $end_date) {
    if (empty($end_date)) {
      $end_date = $start_date;
    }
    $params = [
      'method' => $method,
      'date' => $start_date . ',' . $end_date,
    ];
    return $this->endpoint_base . '&' . http_build_query($params);
  }

  /**
   *
   */
  private function getLastVisitsDetails($start_date, $end_date) {
    $url = $this->getEndpoint('Live.getLastVisitsDetails', $start_date, $end_date);
    return $this->getJson($url);
  }

  /**
   *
   */
  private function getJson($url) {
    $client = \Drupal::httpClient();
    $response = $client->get($url);
    return json_decode($response->getBody(), TRUE);
  }

}
