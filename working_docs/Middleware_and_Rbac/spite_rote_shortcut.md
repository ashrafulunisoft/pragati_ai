Below are **ready-to-use Laravel Tinker commands** for **Spatie Laravel-Permission** 👇
You can copy-paste them **directly into `php artisan tinker`**.

---

## 🔹 Open Tinker

```bash
php artisan tinker
```

---

# 🟢 ROLE (CRUD + Assign)

### ✅ Create Role

```php
use Spatie\Permission\Models\Role;

Role::create(['name' => 'admin']);
Role::create(['name' => 'staff']);
```

---

### ✅ Get / View Roles

```php
Role::all();
Role::find(1);
Role::where('name', 'admin')->first();
```

---

### ✅ Update Role

```php
$role = Role::where('name', 'staff')->first();
$role->update(['name' => 'employee']);
```

---

### ✅ Delete Role

```php
Role::where('name', 'employee')->delete();
```

---

### ✅ Assign Role to User

```php
use App\Models\User;

$user = User::find(1);
$user->assignRole('admin');
```

---

### ❌ Remove Role from User

```php
$user->removeRole('admin');
```

---

### 🔁 Sync Roles (Remove old, add new)

```php
$user->syncRoles(['staff']);
```

---

### 🔍 Check Role

```php
$user->hasRole('admin');
$user->getRoleNames();
```

---

# 🟢 PERMISSION (CRUD + Assign)

### ✅ Create Permission

```php
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'create-user']);
Permission::create(['name' => 'edit-user']);
Permission::create(['name' => 'delete-user']);
```

---

### ✅ View Permissions

```php
Permission::all();
Permission::find(1);
Permission::where('name', 'edit-user')->first();
```

---

### ✅ Update Permission

```php
$permission = Permission::where('name', 'edit-user')->first();
$permission->update(['name' => 'update-user']);
```

---

### ✅ Delete Permission

```php
Permission::where('name', 'update-user')->delete();
```

---

# 🟢 ASSIGN PERMISSION

### ✅ Assign Permission to Role

```php
$role = Role::where('name', 'admin')->first();
$role->givePermissionTo('create-user');
$role->givePermissionTo(['edit-user', 'delete-user']);
```

---

### ❌ Remove Permission from Role

```php
$role->revokePermissionTo('delete-user');
```

---

### 🔁 Sync Role Permissions

```php
$role->syncPermissions(['create-user', 'edit-user']);
```

---

### ✅ Assign Permission Directly to User

```php
$user->givePermissionTo('edit-user');
```

---

### ❌ Remove Permission from User

```php
$user->revokePermissionTo('edit-user');
```

---

### 🔍 Check Permission

```php
$user->can('edit-user');
$user->getAllPermissions();
```

---

# 🔄 Clear Permission Cache (VERY IMPORTANT)

After create/update/delete:

```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

---

# ⭐ MOST USED (Quick Copy)

```php
$user->assignRole('admin');
$user->syncRoles(['staff']);

$role->syncPermissions(['create-user', 'edit-user']);

$user->can('edit-user');
```

---

If you want, I can also give you:

* ✔ **Seeder version**
* ✔ **API Tinker shortcuts**
* ✔ **Multi-guard (web + api) tinker**
* ✔ **Laravel 12 + Jetstream + Spatie setup**

Just say 👍
