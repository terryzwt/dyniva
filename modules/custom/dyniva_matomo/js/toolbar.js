(function (_, $, Drupal) {
  function matomoPost(data, callback) {
    var api = drupalSettings.dyniva_matomo.api;
    $.post(api, data, function (result) {
      if (_.isString(data.params.idSite)) {
        if (data.params.idSite.indexOf(',') != -1 || data.params.idSite == 'all') {
          result = takeoutTopLevel(result);
        }
      }
      callback(result);
    });
  }

  function takeoutTopLevel(data) {
    if (!_.isObject(data) && !_.isArray(data)) return data;
    var temp = null;
    _.each(data, function (item) {
      if (_.isObject(item)) {
        if (!temp) {
          temp = _.clone(item);
          return;
        }
        _.each(item, function (_item, _key) {
          if (_.isObject(_item)) {
            if (!_.has(temp, _key)) temp[_key] = {};
            _.extend(temp[_key], _item);
          }
          if (_.isArray(_item)) {
            if (!_.has(temp, _key)) temp[_key] = [];
            temp[_key] = _.union(temp[_key], _item);
          }
        });
      }
      if (_.isArray(item)) {
        if (!temp) temp = [];
        _.each(item, function (_item) {
          temp.push(_item);
        });
      }
    });
    if (temp) return temp;
    return data;
  }

  Drupal.behaviors.dyniva_matomo_toolbar = {
    widgetRun: function (id, form_id, context) {
      if (id && drupalSettings.dyniva_matomo.widgets[id]) {
        var auto_refresh = drupalSettings.dyniva_matomo.widgets[id].auto_refresh;

        if (drupalSettings.dyniva_matomo.widgets[id].timer) {
          clearTimeout(drupalSettings.dyniva_matomo.widgets[id].timer);
        }

        function refreshData(id, form_id) {
          var params = _.clone(drupalSettings.dyniva_matomo.params['dyniva-matomo-analytics-toolbar']);
          if (_.isObject(drupalSettings.dyniva_matomo.params[id])) {
            _.extend(params, drupalSettings.dyniva_matomo.params[id]);
          }
          if (_.isObject(drupalSettings.dyniva_matomo.widgets[id].params)) {
            _.extend(params, drupalSettings.dyniva_matomo.widgets[id].params);
          }
          var api_method = drupalSettings.dyniva_matomo.widgets[id].api_method;
          var api_callback = drupalSettings.dyniva_matomo.widgets[id].api_callback;

          if (typeof params['date'] != 'undefined' && params['date'].length == 7) {
            var lastDay = new Date(Date.parse(params['date']));
            lastDay = new Date(lastDay.getFullYear(), lastDay.getMonth() + 1, 0);
            var date1 = params['date'] + "-01";
            var date2 = params['date'] + "-" + lastDay.getDate();
            params['date'] = date1 + ',' + date2;
          }

          var data = {
            'api_method': api_method,
            'params': params
          };
          matomoPost(data, function (result) {
            var callback_function = Drupal.behaviors.dyniva_matomo_toolbar.callback[api_callback]
            var settings = _.clone(drupalSettings.dyniva_matomo.widgets[id]);
            if (_.isObject(settings.params)) {
              settings.params = params;
            }
            callback_function(id, result, context, settings);
          });
        }
        refreshData(id, form_id);
        if (auto_refresh) {
          var refresh_interval = drupalSettings.dyniva_matomo.widgets[id].refresh_interval * 1000;
          drupalSettings.dyniva_matomo.widgets[id].timer = setInterval(refreshData, refresh_interval);
        }
      }
    },
    attach: function (context, settings) {
      var self = this;
      $(document).bind('matomo_params_change', function (event, form_id) {
        if (form_id == 'dyniva-matomo-analytics-toolbar') {
          $('.matomo-widget', context).each(function () {
            var id = $(this).attr('id');
            self.widgetRun(id, form_id, context);
          });
        } else {
          self.widgetRun(form_id, form_id, context);
        }
      });

      // 不依赖toolbar block
      if (_.isArray(drupalSettings.dyniva_matomo.run)) {
        _.each(drupalSettings.dyniva_matomo.run, function (id) {
          self.widgetRun(id, id, context);
        })
      }

      $form = $('.dyniva-matomo-analytics-toolbar-form', context);
      $form.each(function () {
        var form_id = $(this).data('id');

        $('.form-item input, .form-item select', this).each(function () {
          $(this).change(function () {
            var $form = $($(this).prop('form'));
            var form_id = $form.data('id');
            if ($(this).attr('name') == 'date1' || $(this).attr('name') == 'date2') {
              var date1 = $('input[name="date1"]', $form).val();
              var date2 = $('input[name="date2"]', $form).val();
              if (date1 && date2) {
                drupalSettings.dyniva_matomo.params[form_id]['date'] = date1 + ',' + date2;
              }
              $(document).trigger('matomo_params_change', form_id);
              return;
            }
            drupalSettings.dyniva_matomo.params[form_id][$(this).attr('name')] = $(this).val();
            $(document).trigger('matomo_params_change', form_id);
          });
        });

        $(document).trigger('matomo_params_change', form_id);
      });

      // city options
      if ($('select[data-action="city"]').length > 0) {
        var params = _.clone(drupalSettings.dyniva_matomo.params['dyniva-matomo-analytics-toolbar']);
        params['period'] = 'year';
        params['segment'] = 'eventAction==city.content.create';
        var data = {
          'api_method': 'Events.getName',
          'params': params
        };
        matomoPost(data, function (result) {
          $('select[data-action="city"]').each(function () {
            var self = this;
            //$(this).empty();
            _.each(result, function (item) {
              var option = document.createElement("option");
              option.text = item.label;
              self.add(option);
            });
          });
        });
      }
    },
    callback: {
      dyniva_matomo_widget_real_time_visitor_api_callback: function (id, data, context, settings) {
        var widget = $('#' + id, context);
        if (widget && data.length > 0) {
          data = data[0];
          $('.simple-realtime-visitor-counter div', widget).text(data.visitors);
          $('.simple-realtime-elaboration .minutes span', widget).text(settings.params.lastMinutes);
          $('.simple-realtime-elaboration .visits span', widget).text(data.visits);
          $('.simple-realtime-elaboration .actions span', widget).text(data.actions);
        }
      },
      dyniva_matomo_visit_info_per_local_time_api_callback: function (id, data, context, settings) {
        var widget = $('#' + id, context);
        var chart = null;
        if (drupalSettings.dyniva_matomo.widgets[id].chart) {
          chart = drupalSettings.dyniva_matomo.widgets[id].chart;
        } else {
          chart = echarts.init($('.chart-wrapper', widget).get(0), 'dy-chart');
        }
        var options = {
          legend: {},
          toolbox: {
            feature: {
              saveAsImage: {}
            }
          },
          tooltip: {
            trigger: 'axis',
            axisPointer: { // 坐标轴指示器，坐标轴触发有效
              type: 'shadow' // 默认为直线，可选为：'line' | 'shadow'
            }
          },
          dataset: {
            dimensions: [
              'label',
              {
                name: 'nb_visits',
                type: 'int',
                displayName: 'UV'
              },
              {
                name: 'nb_actions',
                type: 'int',
                displayName: 'PV'
              }
            ],
            source: data
          },
          xAxis: {
            type: 'category'
          },
          yAxis: {},
          series: [{
              type: 'line'
            },
            {
              type: 'line'
            }
          ]
        };
        chart.setOption(options);
      },
      dyniva_matomo_visit_over_time_api_callback: function (id, data, context, settings) {
        var widget = $('#' + id, context);
        var chart = null;
        if (drupalSettings.dyniva_matomo.widgets[id].chart) {
          chart = drupalSettings.dyniva_matomo.widgets[id].chart;
        } else {
          chart = echarts.init($('.chart-wrapper', widget).get(0), 'dy-chart');
        }
        var options = {
          legend: {},
          toolbox: {
            feature: {
              saveAsImage: {}
            }
          },
          tooltip: {
            trigger: 'axis',
            axisPointer: {
              type: 'cross',
              label: {
                backgroundColor: '#6a7985'
              }
            }
          },
          dataset: {
            dimensions: [
              'label',
              {
                name: 'nb_visits',
                type: 'int',
                displayName: 'UV'
              },
              {
                name: 'nb_actions',
                type: 'int',
                displayName: 'PV'
              }
            ],
            source: data
          },
          xAxis: {
            type: 'category'
          },
          yAxis: {},
          series: [{
              type: 'line'
            },
            {
              type: 'line'
            }
          ]
        };
        chart.setOption(options);
      },
      dyniva_matomo_visit_real_time_of_day_api_callback: function (id, data, context, settings) {
        var widget = $('#' + id, context);
        var chart = null;
        if (drupalSettings.dyniva_matomo.widgets[id].chart) {
          chart = drupalSettings.dyniva_matomo.widgets[id].chart;
        } else {
          chart = echarts.init($('.chart-wrapper', widget).get(0), 'dy-chart');
        }
        var options = {
          grid: {
            bottom: '16%'
          },
          legend: {
            bottom: '2%'
          },
          toolbox: {
            feature: {
              saveAsImage: {}
            }
          },
          tooltip: {
            trigger: 'axis',
            axisPointer: {
              type: 'cross',
              label: {
                backgroundColor: '#6a7985'
              }
            }
          },
          dataset: {
            dimensions: [
              'label',
              {
                name: 'nb_visits',
                type: 'int',
                displayName: 'UV'
              },
              {
                name: 'nb_actions',
                type: 'int',
                displayName: 'PV'
              }
            ],
            source: data
          },
          xAxis: {
            type: 'category',
            boundaryGap: false
          },
          yAxis: {
            minInterval: 1
          },
          series: [{
              type: 'line'
            },
            {
              type: 'line'
            }
          ]
        };
        chart.setOption(options);
      },
      dyniva_matomo_events_over_time_api_callback: function (id, data, context, settings) {
        if (!data || data.length == 0) return;
        var widget = $('#' + id, context);
        var chart = null;
        if (drupalSettings.dyniva_matomo.widgets[id].chart) {
          chart = drupalSettings.dyniva_matomo.widgets[id].chart;
        } else {
          chart = echarts.init($('.chart-wrapper', widget).get(0), 'dy-chart');
        }
        var dimensions = ['label'];
        var series = [];
        Object.keys(data.category).forEach(function (key) {
          series.push({
            type: 'bar'
          });
          dimensions.push({
            name: key,
            type: 'int',
            displayName: data.category[key]
          });
        });
        var options = {
          legend: {},
          tooltip: {},
          toolbox: {
            feature: {
              saveAsImage: {}
            }
          },
          tooltip: {
            trigger: 'axis',
            axisPointer: {
              type: 'cross',
              label: {
                backgroundColor: '#6a7985'
              }
            }
          },
          dataset: {
            dimensions: dimensions,
            source: data.data
          },
          xAxis: {
            type: 'category'
          },
          yAxis: {},
          series: series
        };
        chart.setOption(options);
      },
      dyniva_matomo_browsers_api_callback: function (id, data, context, settings) {
        var widget = $('#' + id, context);
        var chart = null;
        if (drupalSettings.dyniva_matomo.widgets[id].chart) {
          chart = drupalSettings.dyniva_matomo.widgets[id].chart;
        } else {
          chart = echarts.init($('.chart-wrapper', widget).get(0), 'dy-chart');
        }
        var options = {
          legend: {
            orient: 'vertical',
            right: 'right'
            //          data: legend
          },
          tooltip: {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {d}%"
          },
          dataset: {
            dimensions: [
              'label',
              {
                name: 'nb_visits',
                type: 'int',
                displayName: '访客数'
              }
            ],
            source: data
          },
          series: [{
            name: '浏览器',
            type: 'pie',
            radius: '70%',
            center: ['50%', '60%']
          }]
        };
        chart.setOption(options);
      },
      dyniva_matomo_device_type_api_callback: function (id, data, context, settings) {
        var widget = $('#' + id, context);
        var chart = null;
        if (drupalSettings.dyniva_matomo.widgets[id].chart) {
          chart = drupalSettings.dyniva_matomo.widgets[id].chart;
        } else {
          chart = echarts.init($('.chart-wrapper', widget).get(0), 'dy-chart');
        }
        //    var legend = [];
        var source = [];
        data.forEach(function (item) {
          if (item.nb_visits > 0) {
            source.push(item);
            //      legend.push({'name': item.label,'icon': 'image://'+ item.icon});
          }
        });
        var options = {
          legend: {
            orient: 'vertical',
            right: 'right'
            //          data: legend
          },
          tooltip: {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {d}%"
          },
          dataset: {
            dimensions: [
              'label',
              {
                name: 'nb_visits',
                type: 'int',
                displayName: '访客数'
              }
            ],
            source: source
          },
          series: [{
            name: '终端类型',
            type: 'pie',
            radius: '70%',
            center: ['50%', '60%']
          }]
        };
        chart.setOption(options);
      },
      dyniva_matomo_pages_api_callback: function (id, data, context, settings) {
        console.log(data);
        var widget = $('#' + id, context);
        var table = '<table>';
        table += '<thead><tr><th>URL</th><th>UV</th><th>PV</th></tr><thead><tbody>';
        data.forEach(function (item) {
          table += '<tr><td><a href="' + item.url + '" target="_blank">' + item.label + '</a></td><td>' + item.nb_visits + '</td><td>' + item.nb_hits + '</td></tr>';
        });
        table += '</tbody></table>';
        widget.html(table);
      },
      dyniva_matomo_entry_pages_api_callback: function (id, data, context, settings) {
        var widget = $('#' + id, context);
        var table = '<table>';
        table += '<thead><tr><th>URL</th><th>访客数</th></tr><thead><tbody>';
        data.forEach(function (item) {
          if (item.entry_nb_visits) {
            table += '<tr><td><a href="' + item.url + '" target="_blank">' + item.label + '</a></td><td>' + (item.entry_nb_visits ? item.entry_nb_visits : 0) + '</td></tr>';
          }
        });
        table += '</tbody></table>';
        widget.html(table);
      },
      dyniva_matomo_screen_resolution_api_callback: function (id, data, context, settings) {
        var widget = $('#' + id, context);
        var table = '<table>';
        table += '<thead><tr><th>分辨率</th><th>访客数</th></tr><thead><tbody>';
        data.forEach(function (item) {
          table += '<tr><td>' + item.label + '</a></td><td>' + item.nb_visits + '</td></tr>';
        });
        table += '</tbody></table>';
        widget.html(table);
      },
      dyniva_matomo_events_list_api_callback: function (id, data, context, settings) {
        var rows = [],
          groups = {};
        if (settings.params.date.indexOf(',') != -1) {
          rows = takeoutTopLevel(data);
        } else {
          rows = data;
        }
        groups = _.groupBy(rows, function (item) {
          return item.label;
        });
        rows = [];
        _.each(groups, function (group, label) {
          var count = _.reduce(group, function (memo, item) {
            return memo + item.nb_visits;
          }, 0);
          rows.push({
            label: label,
            count: count
          });
        });
        rows = _.sortBy(rows, function (item) {
          return -1 * item.count;
        });

        var widget = $('#' + id, context);
        var table = '<table>';
        if (typeof settings.table_headers != 'undefined') {
          var titles = settings.table_headers.split(',');
          table += '<thead><tr><th>' + titles[0] + '</th><th>' + titles[1] + '</th></tr><thead><tbody>';
        } else {
          table += '<thead><tr><th>关键词</th><th>热度</th></tr><thead><tbody>';
        }

        if (rows.length > 0) {
          rows.forEach(function (item) {
            table += '<tr><td>' + item.label + '</a></td><td>' + item.count + '</td></tr>';
          });
        } else {
          table += '<tr><td>暂未有数据</a></td><td></td></tr>';
        }
        table += '</tbody></table>';
        widget.html(table);
      },
      dyniva_matomo_visits_summary_api_callback: function (id, data, context, settings) {
        if (data && settings.api_method == 'Live.getCounters') {
          data = data[0];
        }
        $('[data-action]').each(function () {
          var s = $(this).data('action').split(':');
          if (s && _.has(data, s[0]) && $(this).data('action') == (s[0] + ':' + settings.params.id)) {
            $(this).text(data[s[0]]);
          }
        });
      },
      // 市县访问量排行榜Top8
      dyniva_matomo_city_report_api_callback: function (id, data, context, settings) {
        var rows = [],
          names = [],
          counts = [];

        if (settings.params.date.indexOf(',') != -1) {
          _.each(data, function (day) {
            _.each(day, function (item) {
              rows.push(item);
            });
          });
        } else {
          rows = data;
        }
        rows = _.sortBy(rows, function (item) {
          return -1 * item.nb_events;
        });

        _.each(rows, function (item) {
          names.push(item.label);
          counts.push(item.nb_events);
        });

        var widget = $('#' + id, context);
        var chart = null;
        if (drupalSettings.dyniva_matomo.widgets[id].chart) {
          chart = drupalSettings.dyniva_matomo.widgets[id].chart;
        } else {
          chart = echarts.init($('.chart-wrapper', widget).get(0), 'dy-chart');
        }
        var options = {
          grid: {
            bottom: '16%'
          },
          tooltip: {},
          legend: {
            data: ['访问量'],
            bottom: '2%'
          },
          xAxis: {
            data: names
          },
          yAxis: {
            name: '访问量'
          },
          series: [{
            name: '访问量',
            type: 'bar',
            barMaxWidth: 30,
            data: counts
          }]
        };
        chart.setOption(options);
      },
      // 年度发文统计
      dyniva_matomo_events_category_api_callback: function (id, data, context, settings) {
        // TODO: 过滤市
        if (!data || data.length == 0) return;
        var widget = $('#' + id, context);
        var chart = null;
        if (drupalSettings.dyniva_matomo.widgets[id].chart) {
          chart = drupalSettings.dyniva_matomo.widgets[id].chart;
        } else {
          chart = echarts.init($('.chart-wrapper', widget).get(0), 'dy-chart');
        }
        var dimensions = ['label'],
          categories = [],
          series = [],
          report = [];
        _.each(data, function (item, date) {
          var row = {
            label: date
          };
          _.each(item, function (category) {
            row[category.label] = category.nb_events;
            if (_.where(categories, {
                name: category.label
              }).length == 0) {
              series.push({
                type: 'bar',
                barMaxWidth: 30,
                barGap: '2%'
              });
              categories.push({
                name: category.label,
                type: 'int',
                displayName: category.label
              });
            }
          });
          report.push(row);
        });
        _.each(categories, function (category) {
          dimensions.push(category);
        });
        Object.keys(report).forEach(function (key) {
          _.each(categories, function (category) {
            if (!_.has(report[key], category.name)) {
              report[key][category.name] = 0;
            }
          });
        });
        var options = {
          grid: {
            bottom: '16%'
          },
          legend: {
            bottom: '2%'
          },
          toolbox: {
            feature: {
              saveAsImage: {}
            }
          },
          tooltip: {
            trigger: 'axis',
            axisPointer: {
              type: 'cross',
              label: {
                backgroundColor: '#6a7985'
              }
            }
          },
          dataset: {
            dimensions: dimensions,
            source: report
          },
          xAxis: {
            type: 'category'
          },
          yAxis: {
            name: '发文数'
          },
          series: series
        };
        chart.setOption(options);
      },
      dyniva_matomo_widget_publish_summary_api_callback: function (id, data, context, settings) {
        if (settings.params.date.indexOf(',') != -1) {
          data = takeoutTopLevel(data);
        }
        var result = {};
        var city = $('[data-id="'+id+'"] [data-action="city"]', context).val();
        _.each(data, function (content_type) {
          if (!_.has(result, content_type.Events_EventCategory)) {
            result[content_type.Events_EventCategory] = 0;
          }
          if (city != '') {
            if (content_type.Events_EventName == city) {
              result[content_type.Events_EventCategory] += content_type.nb_events;
            }
          } else {
            result[content_type.Events_EventCategory] += content_type.nb_events;
          }
        });
        $('[data-action]').each(function () {
          var self = this;
          if ($(this).data('action') == 'total-counter') {
            $(this).text(0);
            if (!_.isEmpty(result)) {
              $(this).text(_.reduce(result, function (memo, item) {
                return memo + item;
              }), 0);
            }
          }
          if ($(this).data('action') == 'category-counter') {
            var html = $(this).data('prefix');
            var lines = [];
            $(this).text('');
            if (_.isEmpty(result)) return;
            _.each(result, function (item, content_type) {
              var content = $(self).data('template').replace('{0}', content_type).replace('{1}', item);
              lines.push(content);
            });
            html += lines.join($(this).data('separator'));
            $(this).text(html);
          }
        });
      },
      // 各市县月度发文统计总览
      dyniva_matomo_widget_publish_month_summary_api_callback: function (id, data, context, settings) {
        var widget = $('#' + id, context);
        var chart = null;
        if (drupalSettings.dyniva_matomo.widgets[id].chart) {
          chart = drupalSettings.dyniva_matomo.widgets[id].chart;
        } else {
          chart = echarts.init($('.chart-wrapper', widget).get(0), 'dy-chart');
        }
        var months = [];
        var report = [];
        var city = $('[data-id="' + id + '"] [data-action="city"]', context).val();
        _.each(data, function (item, date) {
          months.push((new Date(Date.parse(date))).getDate());
          var r = _.reduce(item, function (memo, _item) {
            if (city != '') {
              if (_item.label == city) {
                return memo + _item.nb_events;
              } else {
                return memo;
              }
            }
            return memo + _item.nb_events;
          }, 0);
          if (r) {
            report.push(r);
          } else
            report.push(0);
        });
        var option = {
          xAxis: {
            type: 'category',
            boundaryGap: false,
            data: months
          },
          yAxis: {
            type: 'value',
            name: '发文数'
          },
          series: [{
            data: report,
            type: 'line',
            areaStyle: {}
          }]
        };
        chart.setOption(option);
      },
      dyniva_matomo_users_summary_api_callback: function (id, data, context, settings) {
        var total = 0,
          roles = {};
        _.each(data, function (day) {
          _.each(day, function (event) {
            total += event.nb_events;
            if (!_.has(roles, event.label)) {
              roles[event.label] = 0;
            }
            roles[event.label] += event.nb_events;
          });
        });
        $('[data-action]').each(function () {
          var self = this;
          if ($(this).data('action') == 'total-counter') {
            $(this).text(total);
          }
          if ($(this).data('action') == 'role-counter') {
            var html = $(this).data('prefix');
            var lines = [];
            $(this).text('');
            if (_.isEmpty(roles)) return;
            _.each(roles, function (item, content_type) {
              var content = $(self).data('template').replace('{0}', content_type).replace('{1}', item);
              lines.push(content);
            });
            html += lines.join($(this).data('separator'));
            $(this).text(html);
          }
        });
      },
      dyniva_matomo_users_summary2_api_callback: function (id, data, context, settings) {
        console.log(settings);
        console.log(data);
        data = takeoutTopLevel(data);
        var total = _.reduce(data, function(memo, item) {
          return memo + item.nb_events;
        }, 0);
        $('[data-action]', context).each(function() {
          if($(this).data('action') == 'total-content-create') {
            $(this).text(total);
          }
        });
      },
      // 市县年度发文排行榜Top8
      dyniva_matomo_widget_city_content_publish_api_callback: function (id, data, context, settings) {
        var widget = $('#' + id, context);
        var chart = null;
        if (drupalSettings.dyniva_matomo.widgets[id].chart) {
          chart = drupalSettings.dyniva_matomo.widgets[id].chart;
        } else {
          chart = echarts.init($('.chart-wrapper', widget).get(0), 'dy-chart');
        }

        var rows = [];
        var dimensions = ['label'],
          series = [],
          report = [];
        _.each(data, function (day) {
          _.each(day, function (event) {
            rows.push({
              content_type: event.Events_EventCategory,
              city: event.Events_EventName,
              count: event.nb_events
            });
          });
        });

        // 文章类型
        var content_types = _.chain(rows).map(function (row) {
          return row.content_type;
        }).uniq().value();
        _.each(content_types, function (content_type) {
          dimensions.push({
            name: content_type,
            type: 'int',
            displayName: content_type
          });
          series.push({
            type: 'bar',
            barMaxWidth: 30,
            barGap: '2%'
          });
        });
        rows = _.groupBy(rows, function (row) {
          return row.city;
        });
        _.each(rows, function (row, city) {
          var _row = [city];
          var numsByType = _.chain(row).groupBy(function (item) {
            return item.content_type;
          }).mapObject(function (items) {
            return _.reduce(items, function (memo, item) {
              return memo + item.count;
            }, 0);
          }).value();
          _.each(content_types, function (content_type) {
            _row.push(_.has(numsByType, content_type) ? numsByType[content_type] : 0);
          });
          report.push(_row);
        });
        report = _.sortBy(report, function (row) {
          return -1 * _.reduce(row, function (memo, item) {
            return memo + (_.isNumber(item) ? item : 0);
          }, 0);
        });

        var options = {
          grid: {
            bottom: '16%'
          },
          legend: {
            bottom: '2%'
          },
          toolbox: {
            feature: {
              saveAsImage: {}
            }
          },
          tooltip: {
            trigger: 'axis',
            axisPointer: {
              type: 'cross',
              label: {
                backgroundColor: '#6a7985'
              }
            }
          },
          dataset: {
            dimensions: dimensions,
            source: report
          },
          xAxis: {
            type: 'category'
          },
          yAxis: {
            name: '发文数',
            minInterval: 1
          },
          series: series
        };
        chart.setOption(options);
      }
    }
  };

})(_, jQuery, Drupal);
