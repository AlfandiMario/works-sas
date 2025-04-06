<template>
	<div>
		<v-dialog v-model="xdialogaction" persistent max-width="350">
			<v-card>
				<v-card-title color="warning" class="headline">Konfirmasi</v-card-title>
				<v-card-text v-html="xmsgaction">

				</v-card-text>
				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn color="primary darken-1" flat @click="closeDialogAction()">OK</v-btn>
					<v-btn color="error darken-1" flat @click="xdialogaction = false">Tutup</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>

		<v-layout wrap>
			<v-flex xs12>
				<v-card class="pt-1 pb-1 pr-1" elevation="3" color="light-blue">
					<v-layout align-center justify-center row shrink fill-height>
						<v-flex xs2>
							<v-card flat>
								<v-img :src="xselected_patient.M_PatientPhotoThumb"
									:lazy-src="xselected_patient.M_PatientPhotoThumb" aspect-ratio="1" width="100%"
									class="light-blue" contain>
							</v-card>

						</v-flex>
						<v-flex xs10>
							<v-card flat>
								<v-layout pt-1 pl-2 pb-1 pr-2 row>
									<v-flex>
										<v-subheader style="color:black;height:auto;padding:0px">
											<h3 style="font-size:x-large" class="font-weight-bold">{{
												xselected_patient.T_OrderHeaderLabNumber }}</h3>
											<v-flex text-md-right>
												<span @click="callPatient()"
													v-if="xselected_patient.statusid === '0' || xselected_patient.statusid === '2'"
													style="font-size:24px;"
													class="icon-medium-fill-base-small xs1 white--text info"><v-icon
														dark>volume_up</v-icon></span>
												<span @click="skip()"
													v-if="xselected_patient.statusid === '1' || xselected_patient.statusid === '3'"
													style="font-size:24px;"
													class="icon-medium-fill-base-small xs1 white--text black"><v-icon
														dark>fast_rewind</v-icon></span>
											</v-flex>
										</v-subheader>

									</v-flex>
								</v-layout>
								<v-layout pl-2 pr-2 row>
									<v-flex xs4>
										<v-layout column>
											<v-flex pt-1>
												<v-text-field v-model="xselected_patient.M_PatientNoReg" label="PID"
													readonly hide-details>
												</v-text-field>
											</v-flex>
										</v-layout>
									</v-flex>
									<v-flex xs8 pl-2>
										<v-layout column>
											<v-flex pt-1>
												<v-layout row>
													<v-flex xs4>
														<v-text-field v-model="xselected_patient.patient_dob"
															label="Tanggal lahir" readonly hide-details>
														</v-text-field>
													</v-flex>
													<v-flex xs8 pl-1>
														<v-text-field
															v-model="xselected_patient.T_OrderHeaderM_PatientAge"
															label="Umur" readonly hide-details>
														</v-text-field>
													</v-flex>
												</v-layout>

											</v-flex>
										</v-layout>
									</v-flex>
								</v-layout>
								<v-layout pb-2 pt-1 pl-2 pr-2 row>
									<v-flex xs8>
										<v-text-field v-model="xselected_patient.patient_fullname" label="Nama" readonly
											hide-details>
										</v-text-field>
									</v-flex>
									<v-flex xs4 pl-1>
										<v-text-field v-model="xselected_patient.M_PatientHp" label="HP" readonly
											hide-details>
										</v-text-field>
									</v-flex>
								</v-layout>
							</v-card>
						</v-flex>
					</v-layout>
				</v-card>
			</v-flex>
		</v-layout>
	</div>
</template>

<style scoped></style>

<script>
module.exports = {
	data: () => ({

	}),
	computed: {
		selected_location: {
			get() {
				return this.$store.state.samplecall.selected_location;
			},
			set(val) {
				this.$store.commit("samplecall/update_selected_location", val);
				// this.searchPatient()
			},
		},
		xselected_patient: {
			get() {
				return this.$store.state.samplecall.selected_patient
			},
			set(val) {
				this.$store.commit("samplecall/update_selected_patient", val)
			}
		},
		xdialogaction: {
			get() {
				return this.$store.state.samplecall.dialog_action
			},
			set(val) {
				this.$store.commit("samplecall/update_dialog_action", val)
			}
		},
		xmsgaction: {
			get() {
				return this.$store.state.samplecall.msg_action
			},
			set(val) {
				this.$store.commit("samplecall/update_msg_action", val)
			}
		}
	},
	methods: {
		closeDialogAction() {
			var act = this.$store.state.samplecall.act
			var sample = this.$store.state.samplecall.selected_sampletype
			var status = 1
			if (act === 'process') {
				status = 3
			}
			if (act === 'skip') {
				status = 2
			}
			if (act === 'samplingprocess') {
				status = 3
			}
			if (act === 'samplingdone') {
				status = 4
			}

			let patients = this.$store.state.samplecall.patients
			let last_idx = patients.length - 1
			//console.log(last_idx)
			let last_patient = patients[last_idx]
			//console.log(last_patient)

			if (act != 'addnewlabel') {
				var patient = this.$store.state.samplecall.selected_patient
				var lastid = this.$store.state.samplecall.last_id
				this.$store.dispatch("samplecall/doaction", {
					act: act,
					id: patient.T_OrderHeaderID,
					xdate: this.$store.state.samplecall.start_date,
					name: this.$store.state.samplecall.name,
					nolab: this.$store.state.samplecall.nolab,
					stationid: patient.T_SampleStationID,
					statusid: this.$store.state.samplecall.selected_status.id,
					orderlocationid: patient.order_location_id,
					antritime: patient.antri_time,
					skiptime: patient.skip_time,
					last_skiptime: last_patient.skip_time,
					statusnextid: status,
					sample: sample,
					lastid: lastid,
					locationid: this.selected_location.locationID,
					companyid: this.$store.state.samplecall.selected_company.id,
					staff: this.$store.state.samplecall.staff
				})
			}
			else {
				var patient = this.$store.state.samplecall.selected_patient
				var lastid = this.$store.state.samplecall.last_id
				this.$store.dispatch("samplecall/addnewlabel", {
					act: act,
					id: patient.T_OrderHeaderID,
					xdate: this.$store.state.samplecall.start_date,
					name: this.$store.state.samplecall.name,
					nolab: this.$store.state.samplecall.nolab,
					stationid: patient.T_SampleStationID,
					statusid: this.$store.state.samplecall.selected_status.id,
					orderlocationid: patient.order_location_id,
					antritime: patient.antri_time,
					skiptime: patient.skip_time,
					last_skiptime: last_patient.skip_time,
					statusnextid: status,
					sample: this.$store.state.samplecall.selected_sampletype,
					lastid: lastid,
					locationid: this.selected_location.locationID,
					companyid: this.$store.state.samplecall.selected_company.id,
					staff: this.$store.state.samplecall.staff
				})
			}


		},
		callPatient() {
			this.$store.commit("samplecall/update_act", 'call')
			this.closeDialogAction()
		},
		processNow(value) {
			if (value === '1') {
				//var patient = this.$store.state.samplecall.selected_patient
				//var msg = "Anda yakin akan merubah status <span style='color:ff5252;font-weight:bold'>PROCESS</span> untuk pasien "+patient.patient_fullname+" ? "
				//this.$store.commit("samplecall/update_msg_action",msg)
				this.$store.commit("samplecall/update_act", 'process')
				this.closeDialogAction()
				//this.$store.commit("samplecall/update_dialog_action",true)
			}

		},
		skip() {
			//var patient = this.$store.state.samplecall.selected_patient
			//var msg = "Anda yakin akan merubah status <span style='color:ff5252;font-weight:bold'>SKIP</span> untuk pasien "+patient.patient_fullname+" ? "
			//this.$store.commit("samplecall/update_msg_action",msg)
			this.$store.commit("samplecall/update_act", 'skip')
			this.closeDialogAction()
			//this.$store.commit("samplecall/update_dialog_action",true)
		},
		patient_photo() {
			var photo = "https://www.sgm-inc.com/wp-content/uploads/2014/06/no-profile-male-img.gif"
			if (this.xselected_patient.M_PatientPhoto) {
				photo = this.xselected_patient.M_PatientPhoto
			}
			console.log(photo)
			return photo
		}
	}
}
</script>
