'use client';

import React, { useState, useMemo } from 'react';
import { Search, ChevronRight, Menu, X, ThumbsUp, ThumbsDown, ArrowRight, ChevronDown } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
import { HELP_DATA, Article } from './data';

export default function HelpCenterPage() {
  // --- STATE ---
  const [activeCategoryId, setActiveCategoryId] = useState<string>(HELP_DATA[0].id);
  const [activeArticleId, setActiveArticleId] = useState<string>(HELP_DATA[0].articles[0].id);
  
  // CHANGED: Now storing only ONE string (or null) instead of an array
  const [expandedCategoryId, setExpandedCategoryId] = useState<string | null>(HELP_DATA[0].id);
  
  const [searchQuery, setSearchQuery] = useState('');
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  // --- DERIVED STATE ---
  const activeCategory = HELP_DATA.find(c => c.id === activeCategoryId) || HELP_DATA[0];
  const activeArticle = activeCategory.articles.find(a => a.id === activeArticleId) || activeCategory.articles[0];

  // Powerful Search Algorithm
  const searchResults = useMemo(() => {
    if (!searchQuery) return null;
    const query = searchQuery.toLowerCase();
    
    const results: { category: string; categoryId: string; article: Article }[] = [];
    HELP_DATA.forEach(cat => {
      cat.articles.forEach(art => {
        if (art.title.toLowerCase().includes(query) || art.content.toLowerCase().includes(query)) {
          results.push({ category: cat.title, categoryId: cat.id, article: art });
        }
      });
    });
    return results;
  }, [searchQuery]);

  // --- ACTIONS ---

  // Toggle a category: If it's already open, close it (null). If different, open it.
  const toggleCategory = (catId: string) => {
    setExpandedCategoryId(prev => (prev === catId ? null : catId));
  };

  // Handle selecting an article (Auto-expand its category & Close others)
  const handleSelectArticle = (catId: string, artId: string) => {
    setActiveCategoryId(catId);
    setActiveArticleId(artId);
    setExpandedCategoryId(catId); // This forces only THIS category to be open
    setSearchQuery(''); 
    setIsMobileMenuOpen(false);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans selection:bg-blue-100 dark:selection:bg-blue-900">
      
      {/* 1. HERO / SEARCH HEADER */}
      <header className="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
          
          {/* Logo / Mobile Toggle */}
          <div className="flex items-center gap-3">
            <button 
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="lg:hidden p-2 -ml-2 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400"
            >
              {isMobileMenuOpen ? <X size={24}/> : <Menu size={24}/>}
            </button>
            <div className="flex items-baseline gap-2">
              <h1 className="text-xl font-bold tracking-tight">Help Center</h1>
              <span className="hidden sm:inline-block text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400">
                v2.2
              </span>
            </div>
          </div>

          {/* Omni-Search Bar */}
          <div className="flex-1 max-w-2xl relative group">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Search className="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
            </div>
            <input
              type="text"
              className="block w-full pl-10 pr-3 py-2.5 bg-slate-100 dark:bg-slate-800 border-none rounded-xl leading-5 
                         placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:bg-white dark:focus:bg-slate-900 transition-all shadow-inner"
              placeholder="Search for 'Taxation', 'KYC', 'Transfer'..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
            
            {/* SEARCH DROPDOWN RESULTS */}
            <AnimatePresence>
              {searchQuery && (
                <motion.div 
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: 10 }}
                  className="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 max-h-96 overflow-y-auto z-50"
                >
                  {searchResults && searchResults.length > 0 ? (
                    <ul className="py-2">
                      {searchResults.map((res, idx) => (
                        <li key={idx}>
                          <button
                            onClick={() => handleSelectArticle(res.categoryId, res.article.id)}
                            className="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 flex flex-col gap-0.5 border-b border-slate-100 dark:border-slate-800/50 last:border-0"
                          >
                            <span className="text-sm font-semibold text-slate-800 dark:text-slate-200">{res.article.title}</span>
                            <span className="text-xs text-slate-500 flex items-center gap-1">
                              In {res.category} <ChevronRight size={10} />
                            </span>
                          </button>
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <div className="p-8 text-center text-slate-500">
                      <p>No results found for "{searchQuery}"</p>
                    </div>
                  )}
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        </div>
      </header>

      {/* 2. MAIN SPLIT LAYOUT */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <div className="flex flex-col lg:flex-row gap-12">
          
          {/* SIDEBAR (True Accordion Navigation) */}
          <aside className={`
            fixed inset-0 z-20 bg-white/95 dark:bg-slate-950/95 backdrop-blur-sm lg:static lg:bg-transparent lg:w-72 lg:block
            transform ${isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
            transition-transform duration-300 ease-in-out lg:flex-shrink-0
            overflow-y-auto p-6 lg:p-0 border-r border-slate-200 dark:border-slate-800 lg:border-none
            scrollbar-hide
          `}>
             <div className="lg:hidden flex justify-between items-center mb-6">
                <span className="font-bold text-lg">Categories</span>
                <button onClick={() => setIsMobileMenuOpen(false)}><X/></button>
            </div>

            <nav className="space-y-4">
              {HELP_DATA.map((category) => {
                const isExpanded = expandedCategoryId === category.id;
                const isActiveCategory = activeCategoryId === category.id;

                return (
                  <div key={category.id} className="rounded-xl overflow-hidden transition-colors">
                    
                    {/* Category Header */}
                    <button 
                      onClick={() => toggleCategory(category.id)}
                      className={`
                        w-full flex items-center justify-between p-3 text-sm font-bold tracking-wide uppercase transition-all
                        ${isActiveCategory 
                          ? 'text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-800/50' 
                          : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800/30'}
                        rounded-lg
                      `}
                    >
                      <div className="flex items-center gap-2">
                        <category.icon className={`w-4 h-4 ${isActiveCategory ? 'text-blue-600' : ''}`} />
                        {category.title}
                      </div>
                      <ChevronDown 
                        className={`w-4 h-4 transition-transform duration-300 ${isExpanded ? 'rotate-180' : ''}`} 
                      />
                    </button>

                    {/* Animated Article List */}
                    <AnimatePresence initial={false}>
                      {isExpanded && (
                        <motion.ul 
                          initial="collapsed"
                          animate="open"
                          exit="collapsed"
                          variants={{
                            open: { opacity: 1, height: "auto", marginTop: 8 },
                            collapsed: { opacity: 0, height: 0, marginTop: 0 }
                          }}
                          transition={{ duration: 0.3, ease: [0.04, 0.62, 0.23, 0.98] }} 
                          className="space-y-1 ml-2 border-l border-slate-200 dark:border-slate-800 overflow-hidden"
                        >
                          {category.articles.map((article) => (
                            <li key={article.id}>
                              <button
                                onClick={() => handleSelectArticle(category.id, article.id)}
                                className={`
                                  group flex items-center justify-between w-full text-left pl-4 pr-2 py-2 text-sm rounded-r-lg transition-all
                                  ${activeArticleId === article.id 
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

          {/* CONTENT AREA (Reader) */}
          <section className="flex-1 min-w-0">
            <div className="max-w-3xl">
              
              {/* Breadcrumbs */}
              <nav className="flex items-center gap-2 text-sm text-slate-500 mb-8">
                <span className="hover:text-slate-900 dark:hover:text-slate-200 cursor-pointer">Support</span>
                <ChevronRight className="w-4 h-4" />
                <span className="font-medium text-slate-900 dark:text-slate-200">{activeCategory.title}</span>
              </nav>

              {/* Article Content */}
              <motion.div
                key={activeArticle.id}
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.3 }}
              >
                <div className="mb-8">
                  <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white mb-4 leading-tight">
                    {activeArticle.title}
                  </h1>
                  <div className="flex items-center gap-3 text-sm text-slate-500">
                    <span className="px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                      5 min read
                    </span>
                    <span>Updated Recently</span>
                  </div>
                </div>

                {/* Typography Wrapper */}
                <article className="prose prose-slate dark:prose-invert max-w-none 
                  prose-headings:font-bold prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4
                  prose-p:leading-relaxed prose-p:text-slate-600 dark:prose-p:text-slate-300
                  prose-a:text-blue-600 dark:prose-a:text-blue-400 prose-a:no-underline hover:prose-a:underline
                  prose-strong:text-slate-900 dark:prose-strong:text-white
                  prose-li:marker:text-slate-300
                ">
                  <div dangerouslySetInnerHTML={{ __html: activeArticle.content }} />
                </article>

                {/* Correct Usage */}
                {/* The 'key' ensures the form resets when you switch articles */}
                <ArticleFeedback articleId={activeArticle.id} key={activeArticle.id} />

                {/* Related Links / Footer CTA */}
                <div className="mt-12 p-6 rounded-2xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/50">
                  <h4 className="font-semibold text-blue-900 dark:text-blue-100 mb-2">Still need help?</h4>
                  <p className="text-sm text-blue-700 dark:text-blue-300 mb-4">Our support team is available Mon-Fri, 10 AM - 6 PM IST.</p>
                  <a href="/contact" className="inline-flex items-center text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                    Contact Support <ArrowRight className="w-4 h-4 ml-1" />
                  </a>
                </div>

              </motion.div>
            </div>
          </section>

        </div>
      </main>
    </div>
  );
}

// --- NEW FEEDBACK COMPONENT ---
function ArticleFeedback({ articleId }: { articleId: string }) {
  const [status, setStatus] = useState<'idle' | 'helpful' | 'unhelpful' | 'submitting' | 'submitted'>('idle');
  const [comment, setComment] = useState('');

  const submitFeedback = async (isHelpful: boolean, feedbackComment?: string) => {
    setStatus('submitting');
    
    try {
      // Replace with your actual Laravel API URL
      await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/help-center/feedback`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          article_id: articleId,
          is_helpful: isHelpful,
          comment: feedbackComment
        })
      });
      setStatus('submitted');
    } catch (error) {
      console.error('Feedback failed', error);
      // Even if it fails, show success to the user to not disrupt their flow
      setStatus('submitted'); 
    }
  };

  if (status === 'submitted') {
    return (
      <motion.div 
        initial={{ opacity: 0, y: 10 }} 
        animate={{ opacity: 1, y: 0 }}
        className="mt-16 p-6 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 text-center"
      >
        <p className="text-green-800 dark:text-green-300 font-medium">Thank you! Your feedback helps us improve.</p>
      </motion.div>
    );
  }

  return (
    <div className="mt-16 pt-8 border-t border-slate-200 dark:border-slate-800">
      
      {/* Initial State: Simple Buttons */}
      {status === 'idle' && (
        <div className="animate-in fade-in slide-in-from-bottom-2">
          <h4 className="text-sm font-semibold text-slate-900 dark:text-white mb-4">Did this answer your question?</h4>
          <div className="flex gap-4">
            <button 
              onClick={() => submitFeedback(true)}
              className="flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-700 dark:hover:text-green-400 hover:border-green-200 transition-all text-sm font-medium"
            >
              <ThumbsUp className="w-4 h-4" /> Yes, thanks
            </button>
            <button 
              onClick={() => setStatus('unhelpful')}
              className="flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-700 dark:hover:text-red-400 hover:border-red-200 transition-all text-sm font-medium"
            >
              <ThumbsDown className="w-4 h-4" /> Not really
            </button>
          </div>
        </div>
      )}

      {/* "No" Clicked: Show Text Area */}
      {status === 'unhelpful' && (
        <motion.div initial={{ opacity: 0, height: 0 }} animate={{ opacity: 1, height: 'auto' }}>
          <h4 className="text-sm font-semibold text-slate-900 dark:text-white mb-3">How can we improve this article?</h4>
          <textarea
            className="w-full p-3 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:ring-2 focus:ring-blue-500 outline-none mb-3"
            rows={3}
            placeholder="e.g., The steps for transfer were unclear..."
            value={comment}
            onChange={(e) => setComment(e.target.value)}
          />
          <div className="flex gap-3">
            <button 
              onClick={() => submitFeedback(false, comment)}
              disabled={!comment.trim()}
              className="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-medium rounded-lg disabled:opacity-50"
            >
              Submit Feedback
            </button>
            <button 
              onClick={() => setStatus('idle')}
              className="px-4 py-2 text-slate-500 hover:text-slate-800 text-sm font-medium"
            >
              Cancel
            </button>
          </div>
        </motion.div>
      )}
    </div>
  );
}