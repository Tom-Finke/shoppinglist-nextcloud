<template>
	<div id="content" class="app-shoppinglist">
		hi
		<AppNavigation>
			<AppNavigationNew v-if="!loading"
				:text="t('shoppinglist', 'New note')"
				:disabled="false"
				button-id="new-shoppinglist-button"
				button-class="icon-add"
				@click="newNote" />
			<ul>
				<AppNavigationItem v-for="note in notes"
					:key="note.id"
					:title="note.title ? note.title : t('shoppinglist', 'New note')"
					:class="{active: currentNote && currentNote.id === note.id}"
					@click="openNote(note)">
					<template slot="icon">
						<div class="container0">
							<ColorPicker v-model="note.color" @input="saveNote(note)">
								<font-awesome-icon icon="circle" size="2x" :style="{color: note.color}" />
							</ColorPicker>
						</div>
					</template>
					<template slot="actions">
						<ActionButton
							icon="icon-delete"
							@click="deleteNote(note)">
							{{ t('shoppinglist', 'Delete note') }}
						</ActionButton>
					</template>
				</AppNavigationItem>
			</ul>
		</AppNavigation>
		<AppContent>
			<div v-if="currentNote" class="shopping_list_frame">
				<div
					class="title_div"
					:style="{'background-color': 'color' in currentNote ? currentNote.color : 'white', height: '50px', 'text-align': 'center', 'display': 'inline-block', 'vertical-align': 'middle'}">
					<input v-model="currentNote.title"
						type="text"
						class="title"
						:style="{'border': 'none', 'background-color': currentNote.color}"
						@change="saveNote()">
				</div>

				<input ref="title"
					v-model="newItemText"
					type="text"
					:disabled="updating"
					class="newItemInput"
					:placeholder="t('shoppinglist', 'Add an Item')"
					@input="suggestItem"
					@keydown="$event.key=='Enter'? addNewItem(suggestedItems[0]) : null">
				<table>
					<tr v-for="(item, index) in suggestedItems"
						:key="'suggested'+index">
						<td>{{ item.name }}</td>
						<td><input v-model="item.amount" :style="{color: 'gray', 'text-align': 'right', 'border': 'none'}"></td>
						<td>
							<span @click="addNewItem(item)">
								<font-awesome-icon icon="plus" />
							</span>
						</td>
					</tr>
					<tr v-for="item in currentNote.items.filter(item => item.active == true)"
						:key="item.id"
						class="item-wrapper__item"
						@contextmenu.prevent.stop="handleContextMenu($event, item)">
						<td>
							<input v-model="item.name"
								type="text"
								:style="{'border': 'none'}"
								@change="saveNote()">
						</td>
						<td><input v-model="item.amount" :style="{color: 'gray', 'text-align': 'right', 'border': 'none'}" @change="saveNote()"></td>
						<td>
							<span @click="checkboxChange(item, false)">
								<font-awesome-icon :icon="['far', 'square']" />
							</span>
						</td>
					</tr>
					<tr class="spacer">
						<td class="spacer" colspan="3">
							{{ t('shoppinglist', 'recent') }}
						</td>
					</tr>

					<tr v-for="item in currentNote.items.filter(item => item.active == false)"
						:key="item.id"
						class="item-wrapper__item"
						@contextmenu.prevent.stop="handleContextMenu($event, item)">
						<td colspan="2" :style="{color: 'gray', 'min-width':'200px'}">
							<input v-model="item.name"
								type="text"
								:style="{color: 'gray', 'border': 'none'}"
								@change="saveNote()">
						</td>
						<td>
							<span @click="checkboxChange(item, true)">
								<font-awesome-icon icon="plus" :style="{'color': currentNote.color}" />
							</span>
						</td>
					</tr>
				</table>
			</div>
			<div v-else id="emptycontent">
				<div class="icon-file" />
				<h2>{{ t('shoppinglist', 'Open or create a list to get started') }}</h2>
			</div>
		</AppContent>
		<VueSimpleContextMenu
			:ref="'vueSimpleContextMenu'"
			:element-id="'contextMenu'"
			:options="[{name: t('shoppinglist', 'delete')}]"
			@option-clicked="deleteItem($event)" />
	</div>
</template>
<script src="https://kit.fontawesome.com/c93704883a.js" crossorigin="anonymous"></script>
<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'
// import ColorPicker from '@nextcloud/vue/dist/Components/ColorPicker'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import '@nextcloud/dialogs/styles/toast.scss'
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

import { library } from '@fortawesome/fontawesome-svg-core'
import { faSpinner, faPlus, faCircle } from '@fortawesome/free-solid-svg-icons'
import { faSquare } from '@fortawesome/free-regular-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { v4 as uuidv4 } from 'uuid';

import 'vue-simple-context-menu/dist/vue-simple-context-menu.css'

import VueSimpleContextMenu from 'vue-simple-context-menu'

library.add(faSpinner, faPlus, faSquare, faCircle)
export default {
	name: 'App',
	components: {
		ActionButton,
		AppContent,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNew,
		FontAwesomeIcon,
		// ColorPicker,
		Actions,
		VueSimpleContextMenu
	},
	data() {
		return {
			notes: [],
			currentNote: null,
			updating: false,
			loading: true,
			checked: [],
			newItemText: "",
			suggestedItems: []
		}
	},
	computed: {

		/**
		 * Returns true if a note is selected and its title is not empty
		 * @returns {Boolean}
		 */
		savePossible() {
			return this.currentNote && this.currentNote.title !== ''
		},
	},
	/**
	 * Fetch list of notes when the componenat is loaded
	 */
	async mounted() {
		try {
			const response = await axios.get(generateUrl('/apps/shoppinglist/lists'))
			this.notes = response.data
			console.warn("received:")
			console.log(this.notes)

		} catch (e) {
			console.error(e)
			showError(t('shoppinglist', 'Could not fetch notes'))
		}
		this.loading = false
		console.log("What is going on")
	},

	methods: {
		/**
		 * Create a new note and focus the note content field automatically
		 * @param {Object} note Note object
		 */
		openNote(note) {
			if (this.updating) {
				return
			}
			this.currentNote = note
		},
		checkboxChange(item, newState){
				item.active = newState
				item.editedDate = new Date().toISOString()
				this.$forceUpdate()
				this.saveNote()
		},
		handleContextMenu(event, item){
			this.$refs.vueSimpleContextMenu.showMenu(event, item)
		},
		suggestItem(){
			if(this.newItemText && this.newItemText.trim() !== ''){
				let item = {}
				let units = ["k?g", "m?l"] //these are the units supported by default
				//When a user adds an item with a custom unit of measurement, it becomes part of the available units
				for (let existingItem of this.currentNote.items){
					let unit = existingItem.amount.replace(new RegExp("[0-9]", "g"), "").trim()
					units.push(unit)
				}
				let regexString = "[0-9]+\\s?("+units.join("|")+")?"
				let regex = new RegExp(regexString, "gi") //global match, ignoring case
				let match = this.newItemText.match(regex)
				item.amount= match ? match[0] : ""
				item.name = this.newItemText.replace(item.amount, "")
				this.suggestedItems = [item]
				for (let existingItem of this.currentNote.items){
					if (existingItem.name.includes(this.newItemText)){
						this.suggestedItems.push(Object.assign({}, existingItem))
					}
				}
			}
		},
		addNewItem(item){
			console.log(item)
			if(item && item.name && item.name.trim() !== ''){
			item.active = true
			item.createdDate = new Date().toISOString()
			item.id = uuidv4() //New items get a uuid in order to identify them
			this.suggestedItems = []
			this.newItemText = null
			this.currentNote.items.push(item)
			this.saveNote()

			}
		},
		deleteItem(event){
			try {
				this.currentNote.items = this.currentNote.items.filter((item) => item.id != event.item.id)
				this.saveNote()
				showSuccess(t('shoppinglist', 'Item deleted'))
			} catch (e) {
				console.error(e)
				showError(t('shoppinglist', 'Could not create the note'))
			}
		},
		/**
		 * Action tiggered when clicking the save button
		 * create a new note or save
		 */
		saveNote(note=this.currentNote) {
			if (note.id === -1) {
				this.createNote(note)
			} else {
				this.updateNote(note)
			}
		},
		/**
		 * Create a new note and focus the note content field automatically
		 * The note is not yet saved, therefore an id of -1 is used until it
		 * has been persisted in the backend
		 */
		async newNote() {

			let note = {
				id: uuidv4(),
				author: '',
				title: t('shoppinglist', 'New note'),
				color: '#0082c9',
				items: [],
				createdDate: new Date().toISOString()

			}
			this.updating = true
			console.log(note)
			try {
				const response = await axios.post(generateUrl('/apps/shoppinglist/lists'), {"list": note})
				this.notes.push(note)
				this.openNote(note)
			} catch (e) {
				console.error(e)
				showError(t('shoppinglist', 'Could not create the note'))
			}
			this.updating = false

		},
		/**
		 * Create a new note by sending the information to the server
		 * @param {Object} note Note object
		 */
		async createNote(note) {
			this.updating = true
			try {
				const response = await axios.post(generateUrl('/apps/shoppinglist/lists'), note)
				const index = this.notes.findIndex((match) => match.id === this.currentNoteId)
				this.$set(this.notes, index, response.data)
				this.currentNoteId = response.data.id
			} catch (e) {
				console.error(e)
				showError(t('shoppinglist', 'Could not create the note'))
			}
			this.updating = false
		},
		/**
		 * Update an existing note on the server
		 *
		 * @param {Object} note Note object
		 */
		async updateNote(note) {
			this.updating = true
			console.log("updating:")
			console.log(note)
			axios.put(generateUrl(`/apps/shoppinglist/lists/${note.id}`), {"list": note})
			.then((res) => console.log(res))
			.finally(()=>this.updating = false)
			.catch(e => {
				console.error(e)
				showError(t('shoppinglist', 'Could not update the note'))})

		},
		/**
		 * Delete a note, remove it from the frontend and show a hint
		 *
		 * @param {Object} note Note object
		 */
		deleteNote(note) {
			console.log("delete")
			console.log(note)
			axios.delete(generateUrl(`/apps/shoppinglist/lists/${note.id}`))
			.then((res) => {
				console.log(res)
				this.notes.splice(this.notes.indexOf(note), 1)
				if (this.currentNote && this.currentNote.id === note.id) {
					this.currentNote = null
				}
				showSuccess(t('shoppinglist', 'Note deleted'))
			})
			.catch((e)=>{
				console.error(e)
			showError(t('shoppinglist', 'Could not delete the note'))

			})

		},
	},
}
</script>
<style scoped>
	.app-content {
		padding-top: 40px;

	}

	.shopping_list_frame{
		max-width: 400px;
		width: 400px;
		display: inline-block;
		text-align: center;
		vertical-align: middle;
	}

	#app-content > div {
		width: 100%;
		height: 100%;
		padding: 20px;
		display: flex;
		flex-direction: column;
		flex-grow: 1;
	}

	input[type='text'] {
		width: 80%;
	}

	textarea {
		flex-grow: 1;
		width: 100%;
	}

	table {border-collapse: collapse;}

	td    {padding-right: 6px;}

	#contextMenu{
		border: 1px;
	}

	.title {
		font    : sans-serif;
		font-size: 20px !important;
		text-align: center;
		border  : none;
		padding : 0 10px;
		margin  : 0;
		width   : 240px;
		background: none;
	}

	.title_div{
		width: 100%;
	}

	td.spacer{
		padding-top: 20px;
		padding-bottom: 10px;
	}

	.app-navigation-entry-link * {
		vertical-align: middle;
		text-align: center;
	}
</style>
