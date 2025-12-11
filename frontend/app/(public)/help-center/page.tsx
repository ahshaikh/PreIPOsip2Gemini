'use client';

import React, { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query'; // Import React Query
import api from '@/lib/api'; // Your API utility
import { Search, ChevronRight, Menu, X, ThumbsUp, ThumbsDown, ArrowRight, ChevronDown, BookOpen, Shield, FileText, IndianRupee, HelpCircle, TrendingUp, Landmark, AlertTriangle } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import SupportQuickLinks from '@/components/shared/SupportQuickLinks';

// Icon mapping for dynamic categories
const ICON_MAP: any = {
  'book': BookOpen,
  'shield': Shield,
  'file-text': FileText,
  'rupee': IndianRupee,
  'help-circle': HelpCircle,
  'trending-up': TrendingUp,
  'landmark': Landmark,
  'alert': AlertTriangle,
  'default': BookOpen
};

export default function HelpCenterPage() {
  const [activeCategoryId, setActiveCategoryId] = useState<string | null>(null);
  const [activeArticleId, setActiveArticleId] = useState<string | null>(null);
  const [expandedCategoryId, setExpandedCategoryId] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  // 1. FETCH REAL DATA FROM DB
  const { data: categories, isLoading } = useQuery({
    queryKey: ['kb-menu'],
    queryFn: async () => {
      const response = await api.get('/help-center/menu');
      return response.data;
    }
  });

  // 2. Set defaults once data is loaded
  React.useEffect(() => {
    if (categories && categories.length > 0 && !activeCategoryId) {
      setActiveCategoryId(String(categories[0].id));
      setExpandedCategoryId(String(categories[0].id));
      if (categories[0].articles?.length > 0) {
        setActiveArticleId(String(categories[0].articles[0].id));
      }
    }
  }, [categories, activeCategoryId]);

  // --- DERIVED STATE ---
  const activeCategory = categories?.find((c: any) => String(c.id) === activeCategoryId);
  
  // We need to fetch the full article content when selected
  // (The menu API only returns titles/slugs to save bandwidth)
  const { data: activeArticle, isLoading: isLoadingArticle } = useQuery({
    queryKey: ['kb-article', activeArticleId],
    queryFn: async () => {
      if (!activeArticleId) return null;
      // Find the slug from the menu data first
      let slug = '';
      categories.forEach((c: any) => {
        const art = c.articles.find((a: any) => String(a.id) === activeArticleId);
        if (art) slug = art.slug;
      });
      if (!slug) return null;
      
      const response = await api.get(`/help-center/articles/${slug}`);
      return response.data;
    },
    enabled: !!activeArticleId && !!categories
  });

  // Powerful Search Algorithm (Client-side filtering of the menu)
  const searchResults = useMemo(() => {
    if (!searchQuery || !categories) return null;
    const query = searchQuery.toLowerCase();
    
    const results: any[] = [];
    categories.forEach((cat: any) => {
      cat.articles.forEach((art: any) => {
        if (art.title.toLowerCase().includes(query)) {
          results.push({ category: cat.name, categoryId: String(cat.id), article: art });
        }
      });
    });
    return results;
  }, [searchQuery, categories]);

  // --- HANDLERS ---
  const toggleCategory = (catId: string) => {
    setExpandedCategoryId(prev => (prev === catId ? null : catId));
  };

  const handleSelectArticle = (catId: string, artId: string) => {
    setActiveCategoryId(catId);
    setActiveArticleId(artId);
    setExpandedCategoryId(catId);
    setSearchQuery(''); 
    setIsMobileMenuOpen(false);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans">
      
      {/* HEADER */}
      <header className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <button 
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="lg:hidden p-2 -ml-2 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800"
            >
              {isMobileMenuOpen ? <X size={24}/> : <Menu size={24}/>}
            </button>
            <div className="flex items-baseline gap-2">
              <h1 className="text-xl font-bold tracking-tight">Help Center</h1>
            </div>
          </div>

          <div className="flex-1 max-w-2xl relative group">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Search className="h-5 w-5 text-slate-400" />
            </div>
            <input
              type="text"
              className="block w-full pl-10 pr-3 py-2.5 bg-slate-100 dark:bg-slate-800 border-none rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:bg-white dark:focus:bg-slate-900 transition-all shadow-inner"
              placeholder="Search articles..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
            <AnimatePresence>
              {searchQuery && (
                <motion.div 
                  initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: 10 }}
                  className="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-h-96 overflow-y-auto z-50"
                >
                  {searchResults && searchResults.length > 0 ? (
                    <ul className="py-2">
                      {searchResults.map((res, idx) => (
                        <li key={idx}>
                          <button
                            onClick={() => handleSelectArticle(res.categoryId, String(res.article.id))}
                            className="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 border-b border-slate-100 dark:border-slate-800/50 last:border-0"
                          >
                            <span className="text-sm font-semibold">{res.article.title}</span>
                            <span className="text-xs text-slate-500 block">In {res.category}</span>
                          </button>
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <div className="p-8 text-center text-slate-500"><p>No results found</p></div>
                  )}
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        </div>
      </header>

      {/* MAIN LAYOUT */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">

        {/* Quick Links to Other Support Channels */}
        <div className="mb-12">
          <h2 className="text-2xl font-bold text-slate-900 dark:text-white mb-6">How Can We Help You?</h2>
          <SupportQuickLinks currentPage="help-center" />
        </div>

        <div className="flex flex-col lg:flex-row gap-12">
          
          {/* SIDEBAR */}
          <aside className={`
            fixed inset-0 z-20 bg-white/95 dark:bg-slate-950/95 backdrop-blur-sm lg:static lg:bg-transparent lg:w-72 lg:block
            transform ${isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
            transition-transform duration-300 ease-in-out lg:flex-shrink-0
            overflow-y-auto p-6 lg:p-0 border-r border-slate-200 dark:border-slate-800 lg:border-none scrollbar-hide
          `}>
             <div className="lg:hidden flex justify-between items-center mb-6">
                <span className="font-bold text-lg">Categories</span>
                <button onClick={() => setIsMobileMenuOpen(false)}><X/></button>
            </div>

            <nav className="space-y-4">
              {categories?.map((category: any) => {
                const isExpanded = expandedCategoryId === String(category.id);
                const isActiveCategory = activeCategoryId === String(category.id);
                const Icon = ICON_MAP[category.icon] || BookOpen;

                return (
                  <div key={category.id} className="rounded-xl overflow-hidden transition-colors">
                    <button 
                      onClick={() => toggleCategory(String(category.id))}
                      className={`
                        w-full flex items-center justify-between p-3 text-sm font-bold tracking-wide uppercase transition-all rounded-lg
                        ${isActiveCategory ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-800/50' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/30'}
                      `}
                    >
                      <div className="flex items-center gap-2">
                        <Icon className={`w-4 h-4 ${isActiveCategory ? 'text-blue-600' : ''}`} />
                        {category.name}
                      </div>
                      <ChevronDown className={`w-4 h-4 transition-transform duration-300 ${isExpanded ? 'rotate-180' : ''}`} />
                    </button>

                    <AnimatePresence initial={false}>
                      {isExpanded && (
                        <motion.ul 
                          initial="collapsed" animate="open" exit="collapsed"
                          variants={{ open: { opacity: 1, height: "auto", marginTop: 8 }, collapsed: { opacity: 0, height: 0, marginTop: 0 } }}
                          transition={{ duration: 0.3 }} 
                          className="space-y-1 ml-2 border-l border-slate-200 dark:border-slate-800 overflow-hidden"
                        >
                          {category.articles?.map((article: any) => (
                            <li key={article.id}>
                              <button
                                onClick={() => handleSelectArticle(String(category.id), String(article.id))}
                                className={`
                                  group flex items-center justify-between w-full text-left pl-4 pr-2 py-2 text-sm rounded-r-lg transition-all
                                  ${activeArticleId === String(article.id) 
                                    ? 'text-blue-600 dark:text-blue-400 font-medium bg-blue-50 dark:bg-blue-900/20 border-l-2 border-blue-600 -ml-[1px]' 
                                    : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800'
                                  }
                                `}
                              >
                                {article.title}
                              </button>
                            </li>
                          ))}
                        </motion.ul>
                      )}
                    </AnimatePresence>
                  </div>
                );
              })}
            </nav>
          </aside>

          {/* READER AREA */}
          <section className="flex-1 min-w-0">
            <div className="max-w-3xl">
              <nav className="flex items-center gap-2 text-sm text-slate-500 mb-8">
                <span>Support</span>
                <ChevronRight className="w-4 h-4" />
                <span className="font-medium text-slate-900 dark:text-slate-200">{activeCategory?.name || '...'}</span>
              </nav>

              {activeArticle ? (
                <motion.div initial={{ opacity: 0, y: 10 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.3 }}>
                  <div className="mb-8">
                    <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white mb-4 leading-tight">
                      {activeArticle.title}
                    </h1>
		    
		    {/* Display Summary if available */}
		    {activeArticle.summary && (
			<p className="text-lg text-slate-600 dark:text-slate-300 mb-6 leading-relaxed">
			      {activeArticle.summary}
		        </p>
 		    )}

                    <div className="flex items-center gap-3 text-sm text-slate-500">
                      <span className="px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                        {Math.ceil(activeArticle.content.length / 1000)} min read
                      </span>
		      {/* Display Dynamic Last Updated Date */}
		      {activeArticle.last_updated && (
			  <span>
			     Updated {new Date(activeArticle.last_updated).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
		          </span>
 		      )}
                    </div>
                  </div>

                  <article className="prose prose-slate dark:prose-invert max-w-none">
                    <div dangerouslySetInnerHTML={{ __html: activeArticle.content }} />
                  </article>

                  <ArticleFeedback articleId={String(activeArticle.id)} key={activeArticle.id} />
                </motion.div>
              ) : (
                <div className="flex h-64 items-center justify-center text-slate-400">
                  {isLoadingArticle ? <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"/> : <p>Select an article to read</p>}
                </div>
              )}
            </div>
          </section>

        </div>
      </main>
    </div>
  );
}

// --- NEW SMART FEEDBACK COMPONENT ---
function ArticleFeedback({ articleId }: { articleId: string }) {
  const [status, setStatus] = useState<'idle' | 'helpful' | 'unhelpful' | 'submitting' | 'submitted'>('idle');
  const [comment, setComment] = useState('');

  const submitFeedback = async (isHelpful: boolean, feedbackComment?: string) => {
    setStatus('submitting');
    try {
      await api.post('/help-center/feedback', {
        article_id: articleId,
        is_helpful: isHelpful,
        comment: feedbackComment
      });
      setStatus('submitted');
    } catch (error) {
      console.error('Feedback failed', error);
      setStatus('submitted'); 
    }
  };

  if (status === 'submitted') {
    return (
      <div className="mt-16 p-6 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 text-center">
        <p className="text-green-800 dark:text-green-300 font-medium">Thank you! Your feedback helps us improve.</p>
      </div>
    );
  }

  return (
    <div className="mt-16 pt-8 border-t border-slate-200 dark:border-slate-800">
      {status === 'idle' && (
        <div className="animate-in fade-in slide-in-from-bottom-2">
          <h4 className="text-sm font-semibold text-slate-900 dark:text-white mb-4">Did this answer your question?</h4>
          <div className="flex gap-4">
            <button onClick={() => submitFeedback(true)} className="flex items-center gap-2 px-4 py-2 rounded-lg border hover:bg-green-50 text-sm font-medium">
              <ThumbsUp className="w-4 h-4" /> Yes, thanks
            </button>
            <button onClick={() => setStatus('unhelpful')} className="flex items-center gap-2 px-4 py-2 rounded-lg border hover:bg-red-50 text-sm font-medium">
              <ThumbsDown className="w-4 h-4" /> Not really
            </button>
          </div>
        </div>
      )}
      {status === 'unhelpful' && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: 'auto' }}>
          <h4 className="text-sm font-semibold mb-3">How can we improve this article?</h4>
          <textarea className="w-full p-3 text-sm rounded-lg border bg-slate-50 dark:bg-slate-800 mb-3" rows={3} onChange={(e) => setComment(e.target.value)} />
          <div className="flex gap-3">
            <button onClick={() => submitFeedback(false, comment)} className="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm">Submit</button>
            <button onClick={() => setStatus('idle')} className="px-4 py-2 text-slate-500 text-sm">Cancel</button>
          </div>
        </motion.div>
      )}
    </div>
  );
}