<script setup>
  import { ref, onMounted } from 'vue';

  // Reactive variables for state
  const items = ref([]);
  const currentPage = ref(1);
  const limit = ref(50);
  const isLoading = ref(false);
  const errorMessage = ref(null);
  const totalItems = ref(0); // total records in the index table
  const totalPages = ref(1);  // total number of paginated pages

  // Modal and Form state
  const isModalOpen = ref(false);
  const isSubmitting = ref(false);
  const formError = ref(null);
  const isEditMode = ref(false); // true - edit, false - add (create)

  // Model for the new index form
  const newIndexForm = ref({
    post_index: '',
    region_ua: '',
    city: '',
    post_office_ua: '',
    raion_new_ua: '',
    region_en: '',
    settlement: ''
  });

  // Function to fetch data from the API
  const fetchPostIndexes = async (page = 1) => {
    isLoading.value = true;
    errorMessage.value = null;

    try {
      const response = await fetch(`/api/post-indexes?page=${page}`);

      if (!response.ok) {
        console.error(`HTTP error! status: ${response.status}`);
      }

      const json = await response.json();

      // We fill in the data in accordance with the response structure (json.data.items)
      if (json && json.data) {
        items.value = json.data.items || [];
        currentPage.value = json?.data?.page ?? 1;
        limit.value = json?.data?.limit ?? 50;

        totalItems.value = json?.data?.total_items ?? 0;
        totalPages.value = json?.data?.total_pages ?? 1;
        if (totalPages.value < 1) totalPages.value = 1; // prevent negative Pages count


      } else {
        items.value = [];
        totalPages.value = 1;
      }

      // console.log(totalItems.value, totalPages.value);

    } catch (err) {
      errorMessage.value = err.message || 'Failed to fetch data';
    } finally {
      isLoading.value = false;
    }
  }

  // Navigation functions
  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      fetchPostIndexes(page);
    }
  };

  // nextPage and prevPage functions to navigate through pages
  const nextPage = () => {
    fetchPostIndexes(currentPage.value + 1);
  };

  const prevPage = () => {
    if (currentPage.value > 1) {
      fetchPostIndexes(currentPage.value - 1);
    }
  };

  const deleteItem = (item) => {
    if (confirm(`Ви впевнені, що хочете видалити цей індекс ${item.post_index}?`)) {
      fetch(`/api/post-indexes/${item.post_index}`, {
        method: 'DELETE',
      })
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          // After deletion, refresh the current page
          fetchPostIndexes(currentPage.value);
        })
        .catch(err => {
          alert(`Помилка при видаленні: ${err.message}`);
        });
    }
  };

  // Open / Close modal actions (can accept item for editing)
  const openModal = (item = null) => {
    if (item) {
      isEditMode.value = true;
      // copy item data to form fields
      newIndexForm.value = {
        post_index: item.post_index || '',
        region_ua: item.region_ua || '',
        city: item.city || '',
        post_office_ua: item.post_office_ua || '',
        raion_new_ua: item.raion_new_ua || '',
        region_en: item.region_en || '',
        settlement: item.settlement || ''
      };
    } else {
      isEditMode.value = false;
      // clear form fields for create new item
      newIndexForm.value = {
        post_index: '',
        region_ua: '',
        city: '',
        post_office_ua: '',
        raion_new_ua: '',
        region_en: '',
        settlement: ''
      };
    }
    formError.value = null;
    isModalOpen.value = true;
  };

  const closeModal = () => {
    isModalOpen.value = false;
  };


  // Submit new index to backend
  const submitForm = async () => {
    isSubmitting.value = true;
    formError.value = null;

    try {
      const response = await fetch('/api/post-indexes', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(newIndexForm.value)
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        console.error(errorData.message || `Сервер повернув помилку: ${response.status}`);
      }

      // If success, close modal and reload the first page of the table
      closeModal();

      // If we edited an item, stay on the current page; if created new, go to the 1st page
      if (isEditMode.value) {
        await fetchPostIndexes(currentPage.value);
      } else {
        await fetchPostIndexes(1);
      }

    } catch (err) {
      formError.value = err.message || 'Не вдалося зберегти новий індекс';
    } finally {
      isSubmitting.value = false;
    }
  };

  // "Edit" (click on ✎) item trigger
  const editItem = (item) => {
    openModal(item);
  };


  // Load 1-st pagination page when component is mounted
  onMounted(() => {
    fetchPostIndexes(1);
  });
</script>

<template>
  <div class="index-table-container">
    <div class="table-header">
      <h2>Список поштових індексів України</h2>
      <button style="padding: 12px 25px; border-radius: 8px; font-weight: 600;" @click="openModal(null)">
        <span> &#10133; </span> Додати Індекс
      </button>
    </div>
    <!-- Status messages for loading and errors -->
    <div v-if="isLoading" class="status-message info">
      Loading data...
    </div>
    <div v-else-if="errorMessage" class="status-message error">
      {{ errorMessage }}
    </div>

    <!-- Table with data -->
    <div v-else>
      <div class="table-wrapper">
        <table class="simple-table">
          <thead>
          <tr>
            <th>Індекс</th>
            <th>Область (UA)</th>
            <th>Район (Новий UA)</th>
            <th>Населений пункт (UA)</th>
            <th>Відділення (UA)</th>
            <th>Область (EN)</th>
            <th>Населений пункт (EN)</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="item in items" :key="item.post_index">
            <td class="bold-text">{{ item.post_index }}</td>
            <td>{{ item.region_ua }}</td>
            <td>{{ item.raion_new_ua || '—' }}</td>
            <td>{{ item.city }}</td>
            <td>{{ item.post_office_ua || '—' }}</td>
            <td>{{ item.region_en }}</td>
            <td>{{ item.settlement }}</td>
            <td>
              <button class="action-btn" @click="editItem(item)" title="Редагувати індекс"> ✎ </button>
              <button class="action-btn" @click="deleteItem(item)" title="Видалити індекс"> 🗑️ </button>
              <!--<button class="action-btn" @click="deleteItem(item)" title="Видалити"> ❌ </button>-->
            </td>
          </tr>
          <tr v-if="items.length === 0">
            <td colspan="8" class="text-center">Індекси не знайдено</td>
          </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Controls -->
      <div class="pagination-controls">
        <button @click="goToPage(1)" :disabled="currentPage <= 1">
          &lt;&lt;
        </button>
        <button @click="prevPage" :disabled="currentPage <= 1">
          &lt;
        </button>
        <span class="page-info">Сторінка: {{ currentPage }}</span>
        <button @click="nextPage" :disabled="items.length < limit">
          &gt;
        </button>
        <button @click="goToPage(totalPages)" :disabled="currentPage >= totalPages">
          &gt;&gt;
        </button>
      </div>
    </div>

    <!-- Index Form Modal Overlay (Unified for Create and Edit) -->
    <div v-if="isModalOpen" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ isEditMode ? 'Редагувати індекс' : 'Додати новий індекс' }}</h3>
          <button class="close-modal-btn" @click="closeModal">&times;</button>
        </div>

        <form @submit.prevent="submitForm">
          <!-- Form level error display -->
          <div v-if="formError" class="status-message error form-error-msg">
            {{ formError }}
          </div>

          <div class="form-group">
            <label for="post_index">Поштовий індекс *</label>
            <input
                id="post_index"
                v-model="newIndexForm.post_index"
                type="text"
                required
                placeholder="Наприклад: 99999"
                :disabled="isEditMode"
                :title="isEditMode ? 'Індекс не можна змінювати при редагуванні' : ''"
            />
            <small v-if="isEditMode" style="color: #666; font-size: 11px;">
              Унікальний індекс не підлягає зміні. Якщо потрібно змінити сам індекс, створіть новий запис.
            </small>
          </div>

          <div class="form-group">
            <label for="region_ua">Область (UA) *</label>
            <input
                id="region_ua"
                v-model="newIndexForm.region_ua"
                type="text"
                required
                placeholder="Наприклад: Дніпропетровська"
            />
          </div>

          <div class="form-group">
            <label for="city">Населений пункт (UA) *</label>
            <input
                id="city"
                v-model="newIndexForm.city"
                type="text"
                required
                placeholder="Наприклад: Дніпро"
            />
          </div>

          <div class="form-group">
            <label for="post_office_ua">Відділення (UA)</label>
            <input
                id="post_office_ua"
                v-model="newIndexForm.post_office_ua"
                type="text"
                placeholder="Наприклад: Відділення №1"
            />
          </div>

          <!-- Additional optional fields based on table headers -->
          <div class="form-grid">
            <div class="form-group">
              <label for="raion_new_ua">Район (Новий UA)</label>
              <input
                  id="raion_new_ua"
                  v-model="newIndexForm.raion_new_ua"
                  type="text"
                  placeholder="Новий район"
              />
            </div>

            <div class="form-group">
              <label for="region_en">Область (EN)</label>
              <input
                  id="region_en"
                  v-model="newIndexForm.region_en"
                  type="text"
                  placeholder="Region EN"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="settlement">Населений пункт (EN)</label>
            <input
                id="settlement"
                v-model="newIndexForm.settlement"
                type="text"
                placeholder="Settlement EN"
            />
          </div>

          <div class="form-actions">
            <button type="button" class="cancel-btn" @click="closeModal">Скасувати</button>
            <button type="submit" class="submit-btn" :disabled="isSubmitting">
              {{ isSubmitting ? 'Збереження...' : (isEditMode ? 'Зберегти зміни' : 'Зберегти') }}
            </button>
          </div>
        </form>
      </div>
    </div>
    <!-- New Index Modal end -->

  </div>
</template>

<style scoped>
.index-table-container {
  font-family: Arial, sans-serif;
  margin: 30px auto;
  max-width: 1400px;
  padding: 0 15px;
}

h2 {
  color: #2c3e50;
  margin-bottom: 20px;
}

.table-wrapper {
  overflow-x: auto;
  margin-bottom: 20px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.simple-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 14px;
}

.simple-table th,
.simple-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #eee;
}

.simple-table th {
  background-color: #f8f9fa;
  font-weight: bold;
  color: #495057;
}

.simple-table tr:hover {
  background-color: #f1f3f5;
}

.bold-text {
  font-weight: bold;
  color: #1a252f;
}

.text-center {
  text-align: center;
  color: #868e96;
}

.status-message {
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 20px;
  font-weight: bold;
}

.status-message.info {
  background-color: #e8f4fd;
  color: #1d72b8;
}

.status-message.error {
  background-color: #fdf2f2;
  color: #d32f2f;
  border: 1px solid #f5c2c2;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 15px;
  justify-content: center;
  margin-top: 20px;
}

.page-info {
  font-size: 14px;
  font-weight: bold;
}

button {
  padding: 8px 16px;
  border: 1px solid #ced4da;
  background-color: #fff;
  cursor: pointer;
  border-radius: 4px;
  font-size: 14px;
  transition: background-color 0.2s, border-color 0.2s;
}

button:hover:not(:disabled) {
  background-color: #e9ecef;
  border-color: #adb5bd;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.action-btn {
  width: 50px;
  cursor: pointer;
  margin-right: 2px;
  border-radius: 7px;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: #fff;
  padding: 25px;
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
  padding-bottom: 15px;
  margin-bottom: 20px;
}

.modal-header h3 {
  margin: 0;
  color: #2c3e50;
}

.close-modal-btn {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #aaa;
  padding: 0;
  line-height: 1;
}

.close-modal-btn:hover {
  color: #333;
}

.form-group {
  margin-bottom: 15px;
  display: flex;
  flex-direction: column;
  gap: 5px;
  text-align: left;
}

.form-group label {
  font-weight: 600;
  font-size: 13px;
  color: #495057;
}

.form-group input {
  padding: 10px 12px;
  border: 1px solid #ced4da;
  border-radius: 6px;
  font-size: 14px;
}

.form-group input:focus {
  outline: none;
  border-color: #3b5bdb;
  box-shadow: 0 0 0 3px rgba(59, 91, 219, 0.15);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 15px;
}

@media (min-width: 480px) {
  .form-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 25px;
  border-top: 1px solid #eee;
  padding-top: 15px;
}

.cancel-btn {
  background-color: #f1f3f5;
  color: #495057;
  border: 1px solid #dee2e6;
}

.submit-btn {
  background-color: #3b5bdb;
  color: #fff;
  border: 1px solid #364fc7;
}

.submit-btn:hover:not(:disabled) {
  background-color: #364fc7;
}

.form-error-msg {
  padding: 10px;
  font-size: 13px;
  margin-bottom: 15px;
}


</style>