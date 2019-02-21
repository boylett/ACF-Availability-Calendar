<?php

	/**
	 * ACF Availability Calendar Field
	 * @version      0.0.1
	 * @author       Ryan Boylett <https://github.com/boylett/>
	 * @contributors Giedrius <https://codepen.io/dzentbolas/pen/QjXQVw>
	 */

	add_action('acf/include_field_types', function()
	{
		class ACF_Field_Availability_Calendar extends acf_field
		{
			public function __construct()
			{
				$this->name     = 'availability_calendar';
				$this->label    = 'Availability Calendar';
				$this->category = 'Content';
				$this->defaults = array
				(
					"statuses"      => array(),
					"return_format" => "array",
				);

				parent::__construct();
			}

			public function input_admin_enqueue_scripts()
			{
				wp_enqueue_script('jquery');
				wp_enqueue_script('jquery-ui');
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_script('jquery-ui-dialog');
				wp_enqueue_script('jquery-ui-tooltip');

				wp_enqueue_style('jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
			}

			public function input_admin_head()
			{ ?>

				<script type="text/javascript">
					jQuery.datepicker._updateDatepicker_original = jQuery.datepicker._updateDatepicker;
					jQuery.datepicker._updateDatepicker = function(inst)
					{
						jQuery.datepicker._updateDatepicker_original(inst);

						var afterShow = this._get(inst, 'afterShow');

						if(afterShow)
						{
							afterShow.apply((inst.input ? inst.input[0] : null));
						}
					};

					var AvailabilityCalendarColors = [],
						AvailabilityCalendar       = function(container, dialog, input)
						{
							var $cal         = this,
								current_date = null,
								tooltips     = [];

							this.container = jQuery(container).html('');
							this.dialog    = jQuery(dialog).detach();
							this.input     = jQuery(input);

							this.data = [];

							this.datepicker = this.container.datepicker(
							{
								changeMonth:    true,
								changeYear:     true,
								dateFormat:     "yy-mm-dd",
								defaultDate:    "<?=date("Y-01-01")?>",
								onSelect:       function(date, ui)
								{
									current_date = date;

									dialog
										.find('.date-format')
										.html(date);

									dialog
										.dialog('open');

									for(var i in $cal.data)
									{
										if($cal.data[i].status !== undefined && $cal.data[i].date == date)
										{
											dialog
												.find('[name="new_day_status"]')
												.val($cal.data[i].status);

											break;
										}
									}
								},
								numberOfMonths: [6, 2]
							});

							this.set_day = function(date, status)
							{
								if(date && status)
								{
									this.data.push(
									{
										"date":   date,
										"status": status
									});

									this.save();
								}

								return this;
							};

							this.refresh = function()
							{
								for(var i in this.data)
								{
									if(this.data[i].date !== undefined && this.data[i].status !== undefined && AvailabilityCalendarColors[this.data[i].status] !== undefined)
									{
										var date  = jQuery.datepicker.parseDate('yy-mm-dd', this.data[i].date),
											year  = parseInt(jQuery.datepicker.formatDate('yy', date)),
											month = parseInt(jQuery.datepicker.formatDate('mm', date)) - 1,
											day   = parseInt(jQuery.datepicker.formatDate('dd', date)),
											color = AvailabilityCalendarColors[this.data[i].status];

										var cell = this.datepicker
											.find('[data-year="' + year + '"][data-month="' + month + '"]')
											.filter(function()
											{
												return (parseInt(jQuery(this).text()) == day);
											})
											.addClass('status-' + (this.data[i].status.toLowerCase().replace(/[^a-z0-9-_ ]/g, '').replace(/[_ ]/g, '-')))
											.find('> a')
											.css('background', color);

										if(!(color.match(/white/i) || color.match(/fff(fff)?/i)))
										{
											cell.css('color', 'white');
										}
									}
								}

								return this;
							};

							this.load = function()
							{
								var data = this.input.val();

								if(data)
								{
									try
									{
										data = jQuery.parseJSON(data);

										if(data && typeof data == 'object')
										{
											this.data = data;
										}
									}
									catch(e){}
								}

								this.refresh();

								return this;
							};

							this.save = function()
							{
								var data = this.data;

								data = JSON.stringify(data);

								this.input
									.val(data)
									.trigger('change');

								return this;
							};

							dialog.dialog(
							{
								autoOpen: false,
								buttons:
								{
									"Cancel": function()
									{
										dialog.dialog('close');
									},
									"Save": function()
									{
										dialog.dialog('close');

										$cal.set_day(current_date, dialog
											.find('[name="new_day_status"]')
											.val());

										$cal.refresh();
										$cal.save();
									}
								},
								modal: true
							});

							this.load();

							this.datepicker.datepicker('option', 'afterShow', function()
							{
								$cal.refresh();
							});

							return this;
						};
				</script>
				<style type="text/css">
					.acf-availability-calendar-color-code {
						display: inline-block;
						width: 1.4em;
						height: 1em;
						margin: 0 .5em 0 0;
						border: 1px solid #888;
						background: #EEE;
					}
					.acf-availability-calendar-interface .ui-datepicker-inline {
						width: auto !important;
					}
				</style><?
			}

			public function render_field($field)
			{
				$statuses     = acf_decode_choices($field['statuses']);
				$status_table = array_chunk($statuses, 6, true); ?>

				<textarea id="<?=esc_attr($field['id'])?>" name="<?=esc_attr($field['name'])?>" style="display: none;"><?=esc_textarea(json_encode($field['value']))?></textarea>
				<table width="100%" cellpadding="0" cellspacing="10"><?
				$max = 0;

				foreach($status_table as $row)
				{
					$count = count($row);
					$max   = ($count > $max) ? $count : $max; ?>

					<tr><?
					$col = 0;

					foreach($row as $label => $color)
					{ ?>

						<td width="<?=(100 / $max)?>"><span class="acf-availability-calendar-color-code" style="background: <?=esc_attr($color)?>;"></span> <b><?=$label?></b></td><?
						$col ++;
					}

					if($col < $max)
					{ ?>

						<td colspan="<?=($max - $col)?>">&nbsp;</td><?
					} ?>

					</tr><?
				} ?>

				</table>
				<div id="acf-ac-<?=$field['id']?>-modal" title="<?=esc_attr(__('Booking Status', 'acf-availability-calendar'))?>" style="display: none;">
					<p><?=sprintf(__('Select a booking status for %s.', 'acf-availability-calendar'), '<span class="date-format">this date</span>')?></p>
					<select class="widefat" name="new_day_status">
						<option value="">– <?=__('Select Status', 'acf-availability-calendar')?> –</option><?
				foreach($statuses as $label => $color)
				{ ?>

						<option value="<?=esc_attr($label)?>"><?=esc_textarea($label)?></option><?
				} ?>

					</select>
				</div>
				<div id="acf-ac-<?=$field['id']?>" class="acf-availability-calendar-interface">
					<p><?=__('Your browser does not support this feature.', 'acf-availability-calendar')?></p>
					<script type="text/javascript">
						AvailabilityCalendarColors = <?=json_encode($statuses)?>;

						jQuery(function()
						{
							jQuery('#acf-ac-<?=$field['id']?>').each(function()
							{
								jQuery(this).data('AvailabilityCalendar', new AvailabilityCalendar(this, jQuery('#acf-ac-<?=$field['id']?>-modal'), jQuery('#<?=$field['id']?>')));
							});
						});
					</script>
				</div><?
			}

			function render_field_settings($field)
			{
				acf_render_field_setting($field, array
				(
					'label'			=> __('Status List', 'acf-availability-calendar'),
					'instructions'	=> __('Enter each status on a new line.', 'acf-availability-calendar') . '<br /><br />' .
						__('For more control, you may specify both a label and a color like this:', 'acf-availability-calendar') . '<br /><br />' .
						__('Available : green', 'acf-availability-calendar'),
					'type'			=> 'textarea',
					'name'			=> 'statuses',
				));

				acf_render_field_setting($field, array
				(
					'label'        => __('Return Format', 'acf'),
					'instructions' => __('Specify the value returned', 'acf'),
					'type'         => 'select',
					'name'         => 'return_format',
					'choices'      => array
					(
						'array' => __('Array', 'acf'),
						'html'  => __('jQuery Datepicker', 'acf-availability-calendar'),
					)
				));
			}

			public function update_value($value, $post_id, $field)
			{
				$value = json_decode(stripslashes($value));

				return $value;
			}

			public function format_value($value, $post_id, $field)
			{
				if($field['return_format'] == 'html')
				{
					$statuses = acf_decode_choices($field['statuses']);
					$callback = 'function(){var c=jQuery(\'#acf-availability-calendar-' . esc_attr($field['key']) . '\');';

					foreach($value as $item)
					{
						if(isset($item->date) and $item->date and isset($item->status) and $item->status)
						{
							$date = strtotime($item->date);

							$callback .= 'c' .
								'.find(\'td[data-year="' . date("Y", $date) . '"][data-month="' . (date("n", $date) - 1) . '"]\')' .
								'.filter(function(){return (parseInt($(this).text())==' . date("d", $date) . ')})' .
								'.addClass(\'status-' . esc_attr(sanitize_title($item->status)) . '\')' .
								'.find(\'>a\')' .
								'.css(\'background\',\'' . esc_attr($statuses[$item->status]) . '\');';
						}
					}

					$callback .= '}';

					$value =
						'<div id="acf-availability-calendar-' . esc_attr($field['key']) . '"></div>' .
						'<script type="text/javascript">' .
							'window.addEventListener(\'load\',function(){' .
								'jQuery.datepicker._updateDatepicker_original=jQuery.datepicker._updateDatepicker;jQuery.datepicker._updateDatepicker=function(i){jQuery.datepicker._updateDatepicker_original(i);var a=this._get(i,\'afterShow\');if(a)a.apply((i.input?i.input[0]:null))};' .
								'jQuery(\'#acf-availability-calendar-' . esc_attr($field['key']) . '\').datepicker(' .
									str_replace('"afterShowCallback"', $callback, json_encode(array
									(
										"afterShow"      => "afterShowCallback",
										"changeYear"     => "true",
										"dateFormat"     => "yy-mm-dd",
										"defaultDate"    => date("Y-01-01"),
										"numberOfMonths" => array(6, 2),
									))) .
								')' .
							'})' .
						'</script>';
				}

				return $value;
			}
		}

		new ACF_Field_Availability_Calendar();
	});
