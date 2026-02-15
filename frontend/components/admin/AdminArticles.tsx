import React, { useState, useEffect } from 'react';
import axios from 'axios';

// Type definitions
interface Article {
    id: number;
    title: string;
    content: string;
    category_id: string | number;
    category?: string;
}

interface Category {
    id: number;
    name: string;
}

interface Pagination {
    current_page?: number;
    last_page?: number;
    next_page_url?: string | null;
    prev_page_url?: string | null;
    total?: number;
}

interface FormData {
    id: number | null;
    title: string;
    content: string;
    category_id: string | number;
}

export default function AdminArticles() {
    // --- STATE ---
    const [articles, setArticles] = useState<Article[]>([]);
    const [categories, setCategories] = useState<Category[]>([]); // Needed for dropdowns
    const [pagination, setPagination] = useState<Pagination>({}); // Stores link/meta data
    const [loading, setLoading] = useState(false);

    // Modal State
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);

    // Form State (for Edit)
    const [formData, setFormData] = useState<FormData>({
        id: null,
        title: '',
        content: '',
        category_id: ''
    });

    // --- 1. FETCH DATA (With Pagination) ---
    const fetchArticles = (url = '/api/articles') => {
        setLoading(true);
        axios.get(url).then(response => {
            setArticles(response.data.data); // The actual articles
            setPagination({
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                next_page_url: response.data.next_page_url,
                prev_page_url: response.data.prev_page_url,
                total: response.data.total
            });
            setLoading(false);
        }).catch(err => console.error("Error fetching articles:", err));
    };

    // Load initial data
    useEffect(() => {
        fetchArticles();
        // Fetch categories for the edit dropdown
        axios.get('/api/categories').then(res => setCategories(res.data));
    }, []);


    // --- 2. DELETE LOGIC (Fixing the fake delete) ---
    const handleDelete = async (id: number) => {
        if (!confirm("Are you sure you want to delete this article?")) return;

        try {
            await axios.delete(`/api/articles/${id}`);
            // Success! Remove from UI immediately
            setArticles(articles.filter(article => article.id !== id));
            alert("Article deleted successfully.");
        } catch (error) {
            alert("Failed to delete. Check console.");
            console.error(error);
        }
    };


    // --- 3. EDIT LOGIC (Fixing the empty window) ---
    const openEditModal = (article: Article) => {
        // Hydrate the form with the clicked article's data
        setFormData({
            id: article.id,
            title: article.title,
            content: article.content,
            category_id: article.category_id
        });
        setIsEditModalOpen(true);
    };

    const handleUpdate = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            await axios.put(`/api/articles/${formData.id}`, formData);
            alert("Article Updated!");
            setIsEditModalOpen(false);
            fetchArticles(); // Refresh list to show changes
        } catch (error) {
            console.error("Update failed", error);
        }
    };


    // --- RENDER ---
    return (
        <div className="p-6 bg-gray-50 min-h-screen">
            <h1 className="text-2xl font-bold mb-4">Manage Articles ({pagination.total || 0})</h1>

            {/* TABLE */}
            <div className="bg-white shadow rounded-lg overflow-hidden">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-100">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {loading ? (
                            <tr><td colSpan={4} className="text-center p-4">Loading...</td></tr>
                        ) : articles.map(article => (
                            <tr key={article.id}>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{article.id}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{article.title}</td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {article.category || 'N/A'}
                                </td>
                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    {/* EDIT ICON BUTTON */}
                                    <button 
                                        onClick={() => openEditModal(article)}
                                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                                    >
                                        Edit
                                    </button>
                                    
                                    {/* DELETE ICON BUTTON */}
                                    <button 
                                        onClick={() => handleDelete(article.id)}
                                        className="text-red-600 hover:text-red-900"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* PAGINATION CONTROLS */}
            <div className="mt-4 flex justify-between items-center">
                <button
                    disabled={!pagination.prev_page_url}
                    onClick={() => pagination.prev_page_url && fetchArticles(pagination.prev_page_url)}
                    className={`px-4 py-2 border rounded ${!pagination.prev_page_url ? 'opacity-50 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}`}
                >
                    Previous
                </button>
                <span className="text-sm text-gray-600">
                    Page {pagination.current_page} of {pagination.last_page}
                </span>
                <button
                    disabled={!pagination.next_page_url}
                    onClick={() => pagination.next_page_url && fetchArticles(pagination.next_page_url)}
                    className={`px-4 py-2 border rounded ${!pagination.next_page_url ? 'opacity-50 cursor-not-allowed' : 'bg-white hover:bg-gray-100'}`}
                >
                    Next
                </button>
            </div>

            {/* EDIT MODAL */}
            {isEditModalOpen && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
                    <div className="bg-white p-6 rounded shadow-lg w-1/2">
                        <h2 className="text-xl font-bold mb-4">Edit Article</h2>
                        <form onSubmit={handleUpdate}>
                            <div className="mb-4">
                                <label className="block text-gray-700">Title</label>
                                <input 
                                    type="text" 
                                    className="w-full border p-2 rounded"
                                    value={formData.title}
                                    onChange={e => setFormData({...formData, title: e.target.value})}
                                />
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700">Category</label>
                                <select 
                                    className="w-full border p-2 rounded"
                                    value={formData.category_id}
                                    onChange={e => setFormData({...formData, category_id: e.target.value})}
                                >
                                    {categories.map(cat => (
                                        <option key={cat.id} value={cat.id}>{cat.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="mb-4">
                                <label className="block text-gray-700">Content</label>
                                <textarea 
                                    className="w-full border p-2 rounded h-32"
                                    value={formData.content}
                                    onChange={e => setFormData({...formData, content: e.target.value})}
                                />
                            </div>
                            <div className="flex justify-end gap-2">
                                <button 
                                    type="button"
                                    onClick={() => setIsEditModalOpen(false)}
                                    className="px-4 py-2 bg-gray-300 rounded"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit"
                                    className="px-4 py-2 bg-blue-600 text-white rounded"
                                >
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}