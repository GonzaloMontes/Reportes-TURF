# 🚀 Flujo de trabajo con Git: Desarrollo vs Producción

Este proyecto usa **dos ramas principales**:

- **`desarrollo`** → donde se programan y prueban los cambios.
- **`main`** → versión estable en producción.

---

## 📌 Checklist rápido

### 1. Trabajar en desarrollo
```bash
# Cambiar a la rama desarrollo
git checkout desarrollo

# Traer los últimos cambios del servidor
git pull

# Agregar cambios locales
git add .

# Confirmar cambios
git commit -m "Descripción clara del cambio"

# Subir cambios a GitHub
git push
````

---

### 2. Pasar cambios a producción (desarrollo → main)

```bash
# Cambiar a la rama main
git checkout main

# Actualizar main con el servidor
git pull

# Mezclar cambios desde desarrollo
git merge desarrollo

# Subir main actualizado a GitHub
git push
```

---

### 3. Verificar ramas y estado

```bash
# Listar ramas locales
git branch

# Listar ramas remotas
git branch -a

# Ver estado actual
git status
```

---

### 4. Reglas del flujo

* ✅ **Siempre trabajar en `desarrollo`**, no en `main`.
* ✅ **Solo mergear a `main` cuando los cambios estén probados**.
* ✅ Usar commits con mensajes claros y concisos.
* ✅ Opcional: crear Pull Request en GitHub para revisar cambios antes de pasar a producción.

---

🔄 Con este esquema mantenés un flujo simple y robusto:

* **`desarrollo`** → programación y pruebas.
* **`main`** → producción estable.