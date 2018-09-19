<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

/**
 * The "devices" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudiotService = new Google_Service_CloudIot(...);
 *   $devices = $cloudiotService->devices;
 *  </code>
 */
class Google_Service_CloudIot_Resource_ProjectsLocationsGroupsDevices extends Google_Service_Resource
{
  /**
   * List devices in a device registry.
   * (devices.listProjectsLocationsGroupsDevices)
   *
   * @param string $parent The device registry path. Required. For example,
   * `projects/my-project/locations/us-central1/registries/my-registry`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string deviceNumIds A list of device numerical ids. If empty, it
   * will ignore this field. This field cannot hold more than 10,000 entries.
   * @opt_param string pageToken The value returned by the last
   * `ListDevicesResponse`; indicates that this is a continuation of a prior
   * `ListDevices` call, and that the system should return the next page of data.
   * @opt_param string fieldMask The fields of the `Device` resource to be
   * returned in the response. The fields `id`, and `num_id` are always returned
   * by default, along with any other fields specified.
   * @opt_param int pageSize The maximum number of devices to return in the
   * response. If this value is zero, the service will select a default size. A
   * call may return fewer objects than requested, but if there is a non-empty
   * `page_token`, it indicates that more entries are available.
   * @opt_param string gatewayType If `GATEWAY` is specified, only gateways are
   * returned. If `NON_GATEWAY` specified, only non-gateway devices are returned.
   * If `GATEWAY_TYPE_UNSPECIFIED` specified, all devices are returned.
   * @opt_param string deviceIds A list of device string identifiers. If empty, it
   * will ignore this field. For example, `['device0', 'device12']`. This field
   * cannot hold more than 10,000 entries.
   * @return Google_Service_CloudIot_ListDevicesResponse
   */
  public function listProjectsLocationsGroupsDevices($parent, $optParams = array())
  {
    $params = array('parent' => $parent);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_CloudIot_ListDevicesResponse");
  }
}
