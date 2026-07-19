@extends('layouts.admin')

@section('title', 'Роли и права доступа')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🔐 Роли и права доступа</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Управление ролями и правами пользователей</p>
        </div>
        <button onclick="openRoleModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Создать роль
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Список ролей --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Роли</h2>
            <div class="space-y-3" id="roles-list">
                @foreach($roles as $role)
                    <div class="border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition" data-role-id="{{ $role->id }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold">{{ $role->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $role->description }}</p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($role->permissions->take(3) as $permission)
                                        <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded">
                                            {{ $permission->name }}
                                        </span>
                                    @endforeach
                                    @if($role->permissions->count() > 3)
                                        <span class="text-xs text-gray-500">+{{ $role->permissions->count() - 3 }} еще</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="editRole({{ $role->id }})" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @if(!$role->is_system)
                                    <button onclick="deleteRole({{ $role->id }})" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Список прав --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Права доступа</h2>
            <div class="space-y-4">
                @foreach($permissions as $module => $modulePermissions)
                    <div>
                        <h3 class="font-semibold mb-2">{{ $module ?: 'Общие' }}</h3>
                        <div class="space-y-1">
                            @foreach($modulePermissions as $permission)
                                <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <div>
                                        <span class="text-sm font-medium">{{ $permission->name }}</span>
                                        <span class="text-xs text-gray-500 ml-2">{{ $permission->slug }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Модальное окно создания/редактирования роли --}}
<div id="roleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-2xl font-bold mb-4" id="modalTitle">Создать роль</h2>
        <form id="roleForm" onsubmit="saveRole(event)">
            <input type="hidden" id="roleId" name="id">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Название</label>
                    <input type="text" id="roleName" name="name" required class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Slug</label>
                    <input type="text" id="roleSlug" name="slug" required class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Описание</label>
                    <textarea id="roleDescription" name="description" class="w-full border rounded px-3 py-2"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Приоритет</label>
                    <input type="number" id="rolePriority" name="priority" value="0" class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Права доступа</label>
                    <div class="max-h-60 overflow-y-auto border rounded p-3 space-y-2">
                        @foreach($permissions as $module => $modulePermissions)
                            <div class="mb-4">
                                <h4 class="font-semibold mb-2">{{ $module ?: 'Общие' }}</h4>
                                @foreach($modulePermissions as $permission)
                                    <label class="flex items-center space-x-2 p-1 hover:bg-gray-50 dark:hover:bg-gray-700 rounded">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="permission-checkbox">
                                        <span class="text-sm">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Сохранить
                </button>
                <button type="button" onclick="closeRoleModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                    Отмена
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRoleModal(roleId = null) {
    const modal = document.getElementById('roleModal');
    const form = document.getElementById('roleForm');
    const title = document.getElementById('modalTitle');
    
    if (roleId) {
        title.textContent = 'Редактировать роль';
        // Загрузка данных роли через AJAX
        fetch(`/admin/roles/${roleId}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('roleId').value = data.role.id;
                document.getElementById('roleName').value = data.role.name;
                document.getElementById('roleSlug').value = data.role.slug;
                document.getElementById('roleDescription').value = data.role.description || '';
                document.getElementById('rolePriority').value = data.role.priority;
                
                // Отметить выбранные права
                data.role.permissions.forEach(perm => {
                    const checkbox = document.querySelector(`input[value="${perm.id}"]`);
                    if (checkbox) checkbox.checked = true;
                });
            });
    } else {
        title.textContent = 'Создать роль';
        form.reset();
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
    }
    
    modal.classList.remove('hidden');
}

function closeRoleModal() {
    document.getElementById('roleModal').classList.add('hidden');
}

function saveRole(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const roleId = formData.get('id');
    const url = roleId ? `/admin/roles/${roleId}` : '/admin/roles';
    const method = roleId ? 'PUT' : 'POST';
    
    const data = {
        name: formData.get('name'),
        slug: formData.get('slug'),
        description: formData.get('description'),
        priority: parseInt(formData.get('priority')),
        permissions: Array.from(document.querySelectorAll('.permission-checkbox:checked')).map(cb => parseInt(cb.value)),
    };
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Ошибка');
        }
    });
}

function editRole(roleId) {
    openRoleModal(roleId);
}

function deleteRole(roleId) {
    if (!confirm('Удалить эту роль?')) return;
    
    fetch(`/admin/roles/${roleId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Ошибка');
        }
    });
}
</script>
@endsection

